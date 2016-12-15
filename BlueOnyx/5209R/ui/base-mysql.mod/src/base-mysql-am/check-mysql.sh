#!/bin/bash

# Galera hotfix for 5209R:
if [ -f /etc/build ]; then
BUILD=`/bin/cat /etc/build | /bin/grep 5209R | /usr/bin/wc -l`
    if [ $BUILD = "1" ]; then
        #echo "Is 5209R"
	YUMACTIVE=`ps axf|grep yum|grep -v grep|wc -l`
	if [ $YUMACTIVE = "0" ]; then
	        if [ ! -f /usr/lib/systemd/system/mariadb.service ];then
		    rpm -hUv --force /usr/sausalito/swatch/bin/hotfix/*
	            systemctl enable mariadb
	    	    systemctl start mariadb
        	fi
        fi
    fi
fi

/sbin/pidof mysqld >/dev/null
if [ $? == 0 ]; then
  /bin/echo "is running..."
fi


