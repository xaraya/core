#!/bin/sh

# Produce an unofficial package of the repository for the testing team.

# optional username override
if [ $1 ]; then
    USER=$1
fi

# and create the xaraya-all tarball
mkdir -p build/core/stable
REPNAME=$USER@www.xaraya.com:/usr/local/repositories/xaraya/core/stable
bk clone $REPNAME build/core/stable

# modules
mkdir -p build/modules
REPNAME=$USER@www.xaraya.com:/usr/local/repositories/xaraya/modules/stable
bk clone $REPNAME build/modules

# themes
mkdir -p build/themes
REPNAME=$USER@www.xaraya.com:/usr/local/repositories/xaraya/themes
bk clone $REPNAME build/themes

# languages
mkdir -p build/languages
REPNAME=$USER@www.xaraya.com:/usr/local/repositories/xaraya/languages
bk clone $REPNAME build/languages

# run the build script
cd build/core/stable
bk build rebuild

# move the output files and remove working files
mv build/core/stable/build/* build
rm -r build/{core,modules,themes}

