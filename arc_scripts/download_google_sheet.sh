#!/bin/bash

# Usage: ./download_sheet_as_tsv.sh <gid> <output.tsv>

# CONFIGURATION
KEY_FILE="/var/www/vhosts/dibs.mn/arc_scripts/dibsmn-2ba7c8a4b78e.json"
SCOPES="https://www.googleapis.com/auth/drive.readonly"
SHEET_ID="1et-lodIN-lFQEudKEJtX0DHM-cLa63OhsSuYDL0QgU0"

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
HTTP_CODE=$(curl -L -s -w '%{http_code}' -H "Authorization: Bearer $ACCESS_TOKEN" \
  "https://docs.google.com/spreadsheets/d/$SHEET_ID/export?format=csv&gid=$GID" \
  -o "$TEMP_CSV")

# A missing gid or revoked access returns an HTML error page. Without this check
# the page gets converted to TSV and imported as if it were data.
if [ "$HTTP_CODE" != "200" ]; then
    echo "❌ Export failed: HTTP $HTTP_CODE (sheet $SHEET_ID, gid $GID)"
    echo "   $OUTPUT_FILE left unchanged."
    rm -f "$TEMP_CSV"
    exit 1
fi

if head -c 200 "$TEMP_CSV" | grep -qi '<!DOCTYPE html\|<html'; then
    echo "❌ Export returned HTML, not CSV (sheet $SHEET_ID, gid $GID)"
    echo "   $OUTPUT_FILE left unchanged."
    rm -f "$TEMP_CSV"
    exit 1
fi

# Convert CSV to TSV using Python, writing beside the target so a failure
# part-way through leaves the existing file intact.
TEMP_TSV="$OUTPUT_FILE.tmp.$$"
python3 - <<EOF || { echo "❌ Conversion failed; $OUTPUT_FILE left unchanged."; rm -f "$TEMP_TSV"; exit 1; }
import csv

kept = dropped = 0
with open("$TEMP_CSV", newline='', encoding='utf-8') as csv_in, \
     open("$TEMP_TSV", 'w', newline='', encoding='utf-8') as tsv_out:
    reader = csv.reader(csv_in)
    writer = csv.writer(tsv_out, delimiter='\t', quoting=csv.QUOTE_MINIMAL)
    for i, row in enumerate(reader):
        # Formulas filled down past the last sign-up export as blank rows.
        if i > 0 and not (row and row[0].strip()):
            dropped += 1
            continue
        writer.writerow(row)
        kept += 1
print("   rows written: %d (incl header), blank rows dropped: %d" % (kept, dropped))
EOF

chmod 644 "$TEMP_TSV"
mv "$TEMP_TSV" "$OUTPUT_FILE"

#rm -f "$TEMP_CSV"
echo "✅ Sheet downloaded and converted to $OUTPUT_FILE"
