#!/bin/sh

ROOT=/var/mt/xar/core/mail-in/html
CWD=`pwd`

cd $ROOT
export REMOTE_ADDR=127.0.0.1
php5 $ROOT/ws.php mail -u Admin -p 12345 < $CWD/msg_1.txt