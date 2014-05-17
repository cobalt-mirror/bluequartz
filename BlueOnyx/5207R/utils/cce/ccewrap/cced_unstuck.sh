#!/bin/bash
/usr/bin/killall -9 /usr/sausalito/sbin/cced
/usr/bin/killall -9 pperld
/usr/bin/killall -9 cced.init
/sbin/service cced.init rehash
echo "OK"
exit 0