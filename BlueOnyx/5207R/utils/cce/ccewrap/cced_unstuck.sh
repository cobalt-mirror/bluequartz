#!/bin/bash
/usr/bin/killall -9 /usr/sausalito/sbin/cced
/usr/bin/killall -9 pperld
/usr/bin/killall -9 cced.init
ps aux | grep cced | grep -v kill | awk '{print $2}' | xargs kill -9 
/sbin/service cced.init rehash
echo "OK"
exit 0