#!/bin/sh
# Purge AV-SPAM SQL database of SQL entries that are past their retention date.

if [ -f /usr/sausalito/constructor/base/sitestats/purge_avspam.pl ];then
     /usr/sausalito/constructor/base/sitestats/purge_avspam.pl
fi



