#!/bin/bash

# Fix PHP Session dir GID if needed:
if [ -d "/var/lib/php/session" ];then
	SESSPERMS=`ls -la /var/lib/php|grep session|awk '{print $1}'`
	if [ $SESSPERMS != "drwxrwxrwx" ];then
		chmod 777 /var/lib/php/session
	fi
fi

# On 5107R/5108R remove the OS supplied YUM autoupdater, as we bring our own:
if [ -f /etc/cron.daily/yum-autoupdate ] ; then
	rm -f /etc/cron.daily/yum-autoupdate
fi

exit

