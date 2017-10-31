#!/bin/sh

PORT=`cat /etc/httpd/conf/httpd.conf|grep ^Listen|cut -d \  -f2`

/usr/sausalito/swatch/bin/am_apache.pl
/usr/sausalito/swatch/bin/am_apache.exp $PORT


