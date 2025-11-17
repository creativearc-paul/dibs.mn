#!/bin/bash

# need to pass gid (specific sheet)  $1, output name $2
# CONFIGURATION
KEY_FILE="/var/www/vhosts/dibs.mn/arc_scripts/dibsmn-2ba7c8a4b78e.json"
SCOPES="https://www.googleapis.com/auth/drive.readonly"
# (from 2024-2025) SHEET_ID="1ZYgPy1EFS8IFhKAhTqTa6Fppuyf8YJbxAP9u2_feSjw"
# 2025-2026
SHEET_ID="1JMwQjGqUE9QBb5vVrsfilmeNtWFva7QoPQ2ycbxWZqM"
# test sheet 1ZYgPy1EFS8IFhKAhTqTa6Fppuyf8YJbxAP9u2_feSjw
# main 0
# larson test 1030494426
GID="$1"
OUTPUT_FILE=$2

# Check for dependencies
command -v jq >/dev/null || { echo "Please install jq."; exit 1; }

# Parse service account details
ISS=$(jq -r .client_email "$KEY_FILE")
PRIV_KEY_PATH=$(mktemp)
jq -r .private_key "$KEY_FILE" > "$PRIV_KEY_PATH"

# Create JWT header and payload
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

# Create the signature
DATA_TO_SIGN="$HEADER_BASE64.$PAYLOAD_BASE64"
SIGNATURE=$(echo -n "$DATA_TO_SIGN" | openssl dgst -sha256 -sign "$PRIV_KEY_PATH" | openssl base64 -e -A | tr '+/' '-_' | tr -d '=')

JWT="$DATA_TO_SIGN.$SIGNATURE"

# Clean up private key file
rm -f "$PRIV_KEY_PATH"

# Get access token
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

# Download the Google Sheet as CSV (raw)
TEMP_FILE=$(mktemp)
curl -L -s -H "Authorization: Bearer $ACCESS_TOKEN" \
  "https://docs.google.com/spreadsheets/d/$SHEET_ID/export?format=csv&gid=$GID" \
  -o "$TEMP_FILE"

# Ensure every field (including header) is quoted and comma-delimited
awk 'BEGIN{FS=","; OFS=","}
{
    sub(/\r$/, "")               # remove CR from CRLF if present
    for (i=1; i<=NF; i++) {
        gsub(/"/, "\"\"", $i)    # escape internal quotes
        $i="\"" $i "\""          # wrap field in double quotes
    }
    $1=$1                        # force rebuild of the record using OFS
    print
}' "$TEMP_FILE" > "$OUTPUT_FILE"

rm -f "$TEMP_FILE"
echo "Sheet downloaded and quoted to $OUTPUT_FILE"

