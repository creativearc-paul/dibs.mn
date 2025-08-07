#!/bin/bash

# CONFIGURATION
KEY_FILE="/var/www/vhosts/dibs.mn/arc_scripts/dibsmn-2ba7c8a4b78e.json"
SCOPES="https://www.googleapis.com/auth/drive.readonly"
SHEET_ID="1ZYgPy1EFS8IFhKAhTqTa6Fppuyf8YJbxAP9u2_feSjw"
GID="1030494426"
OUTPUT_FILE="sheet.csv"

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

# Download the Google Sheet as CSV
curl -L -s -H "Authorization: Bearer $ACCESS_TOKEN" \
  "https://docs.google.com/spreadsheets/d/$SHEET_ID/export?format=csv&gid=$GID" \
  -o "$OUTPUT_FILE"

echo "Sheet downloaded to $OUTPUT_FILE"
