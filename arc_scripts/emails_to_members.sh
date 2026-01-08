#!/usr/bin/bash

# Usage: ./emails_to_members.sh emails.txt
# emails.txt = one email address per line

INPUT_FILE="$1"

if [[ -z "$INPUT_FILE" || ! -f "$INPUT_FILE" ]]; then
  echo "Usage: $0 emails.txt"
  exit 1
fi

echo "<members>"

while IFS= read -r email || [[ -n "$email" ]]; do
  # Skip empty lines
  [[ -z "$email" ]] && continue

  # Generate md5 hash of "email!!"
  password_hash=$(printf "%s!!" "$email" | md5sum | awk '{print $1}')

  cat <<EOF
    <member>
        <username>$email</username>
        <screen_name>$email</screen_name>
        <password type="md5">$password_hash</password>
        <email>$email</email>
    </member>
EOF

done < "$INPUT_FILE"

echo "</members>"
