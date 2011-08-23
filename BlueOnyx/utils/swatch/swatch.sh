#!/bin/bash

export LANG=en_US
export LC_ALL=en_US.UTF-8
export LINGUAS="en_US ja da_DK de_DE"
exec=/usr/sausalito/sbin/swatch.sh
lockfile=/var/lock/subsys/swatch

if [ -f $lockfile ] ; then
	/bin/find $lockfile -type f -cmin +25 -print | /usr/bin/xargs /bin/rm >/dev/null 2>&1
	#echo "Swatch cronjob is already running. Delaying execution for now.";
	exit
fi

touch $lockfile

if [ -f "/tmp/.swatch.lock" ]; then
        /bin/find /tmp/.swatch.lock -type f -cmin +25 -print | /usr/bin/xargs /bin/rm >/dev/null 2>&1
	#echo "Swatch executeable is already running. Delaying execution for now.";
	exit
else
        /bin/touch /tmp/.swatch.lock
        /usr/sbin/swatch -c /etc/swatch.conf
        /bin/rm -f /tmp/.swatch.lock
fi

/bin/rm -f $lockfile
exit


