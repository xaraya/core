#!/bin/sh

#
# Usage: addxarroot filename
#
# Adds <xar:template xmlns:xar=\"http://xaraya.com/2004/blocklayout\">
# as root tag to the file (and closing it at the end)
#

OTAG="<xar:template xmlns:xar=\"http://xaraya.com/2004/blocklayout\">"
ETAG="</xar:template>"

# First arg is filename and should exist, obviously

if [ ! -e "$1" ]
then   # Bail out if no such file.
  echo "File $1 not found."
  exit 1
fi

cat - $1 <<<$OTAG > $1.new
echo -e "\n" >> $1.new
echo $ETAG >> $1.new
mv $1.new $1
