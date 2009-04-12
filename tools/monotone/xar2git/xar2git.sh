#!/bin/sh
MTN="mtn -d xar2git.db "
MTNHOST="mt.xaraya.com"
MTNMARKS="./xar2git.mtnmarks"
GITMARKS="./xar2git.gitmarks"
AUTHORS="./mtn-authors"

# Simple script to take new revisions from xar mtn repo and sync up a git repository
# Usage:
#  Drop this file and the mtn-authors file in a directory and execute
#  This script can be run repeatedly to incrementally add new revisions, thus
#  effectively tracking a montone repository in git.

# Preparations
if [ ! -f ./xar2git.db ]; then
  echo "No mtn db yet, creating one..."
  $MTN db init
fi
if [ ! -d ./.git ]; then
  echo "No git repository yet, creating one..."
  git init
fi

# 1. Make sure our mtn database is up to data
echo "1. Pulling $MTNHOST for new revisions..."
# Note: with jamaica being on mt.xaraya.com in a separate db, 
# mtn pull mt.xaraya.com com.xaraya.core.{unstable,jamaica} will not work anymore
$MTN pull $MTNHOST "com.xaraya.core.unstable"
$MTN pull $MTNHOST "com.xaraya.core.jamaica"

# 2. Update the git repository, saving import and export marks
MTNARGS="--authors-file=$AUTHORS  --export-marks=$MTNMARKS "
if [ -f $MTNMARKS ]; then
  # Marks file is there, must have been run before, read them in
  MTNARGS="$MTNARGS --import-marks=$MTNMARKS"
fi
echo "2. Exporting these new revisions into git..."
$MTN git_export $MTNARGS | git fast-import --export-marks=$GITMARKS
