#!/bin/sh
#
# Verify SHA256 checksum for all .php files
#
# Note: run this from the xaraya root folder
#
# $ ./developer/bin/checksum.sh
#

FILE="./checksum.sha256"
DIR="html"
IGNORE="html/var/cache/*"

if [[ -f "$FILE" ]]
then
	echo "Checksum file exists"
	sha256sum -c "$FILE" | grep -v ' OK'
	echo "Checksum file checked"
else
	echo "Checksum file does not exist"
	find "$DIR" -path "$IGNORE" -prune -o -name '*.php' -exec sha256sum {} \; > $FILE
	echo "Checksum file created"
fi
