#!/bin/bash

# Usage: ./download_sheet_as_tsv.sh <gid> <output.tsv>

# CONFIGURATION
KEY_FILE="/var/www/vhosts/dibs.mn/arc_scripts/dibsmn-2ba7c8a4b78e.json"
SCOPES="https://www.googleapis.com/auth/drive.readonly"
SHEET_ID="1JMwQjGqUE9QBb5vVrsfilmeNtWFva7QoPQ2ycbxWZqM"

GID="$1"
OUTPUT_FILE="$2"

# Check required tools
command -v jq >/dev/null || { echo "jq not found"; exit 1; }
command -v python3 >/dev/null || { echo "python3 not found"; exit 1; }

# Extract service account credentials
ISS=$(jq -r .client_email "$KEY_FILE")
PRIV_KEY_PATH=$(mktemp)
jq -r .private_key "$KEY_FILE" > "$PRIV_KEY_PATH"

# Generate JWT for OAuth 2.0
HEADER_BASE64=$(echo -n '{"alg":"RS256","typ":"JWT"}' | openssl base64 -e -A | tr '+/' '-_' | tr -d '=')
NOW=$(date +%s)
EXP=$(($NOW + 3600))
PAYLOAD=$(cat <<EOF
{
  "iss":"$ISS",
  "scope":"$SCOPES",
  "aud":"https://oauth2.googleapis.com/token",
  "exp":$EXP,
  "iat":$NOW
}
EOF
)
PAYLOAD_BASE64=$(echo -n "$PAYLOAD" | openssl base64 -e -A | tr '+/' '-_' | tr -d '=')
DATA_TO_SIGN="$HEADER_BASE64.$PAYLOAD_BASE64"
SIGNATURE=$(echo -n "$DATA_TO_SIGN" | openssl dgst -sha256 -sign "$PRIV_KEY_PATH" | openssl base64 -e -A | tr '+/' '-_' | tr -d '=')
JWT="$DATA_TO_SIGN.$SIGNATURE"
rm -f "$PRIV_KEY_PATH"

# Request access token
RESPONSE=$(curl -s -X POST -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=$JWT" \
  https://oauth2.googleapis.com/token)

ACCESS_TOKEN=$(echo "$RESPONSE" | jq -r .access_token)
if [ "$ACCESS_TOKEN" == "null" ] || [ -z "$ACCESS_TOKEN" ]; then
    echo "Failed to obtain access token"
    echo "$RESPONSE"
    exit 1
fi

echo "Access token obtained."

# Download sheet as CSV
TEMP_CSV=$(mktemp)
curl -L -s -H "Authorization: Bearer $ACCESS_TOKEN" \
  "https://docs.google.com/spreadsheets/d/$SHEET_ID/export?format=csv&gid=$GID" \
  -o "$TEMP_CSV"

# Convert CSV to TSV using Python
python3 - <<EOF
import csv

with open("$TEMP_CSV", newline='', encoding='utf-8') as csv_in, \
     open("$OUTPUT_FILE", 'w', newline='', encoding='utf-8') as tsv_out:
    reader = csv.reader(csv_in)
    writer = csv.writer(tsv_out, delimiter='\t', quoting=csv.QUOTE_MINIMAL)
    for row in reader:
        writer.writerow(row)
EOF

#rm -f "$TEMP_CSV"
echo "✅ Sheet downloaded and converted to $OUTPUT_FILE"
