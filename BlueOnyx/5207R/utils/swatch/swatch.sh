#!/bin/bash

export LANG=en_US
export LC_ALL=en_US.UTF-8
export LINGUAS="en_US ja da_DK de_DE"
exec=/usr/sausalito/sbin/swatch.sh
lockfile=/var/lock/subsys/swatch
FIND=`which find`
XARGS=`which xargs`
TOUCH=`which touch`
REM=`which rm`
CCEDUP=`/usr/sausalito/bin/check_cce.pl`

if [ "$CCEDUP" != "SUCCESS" ];then
        /usr/sausalito/bin/cced_unstuck.sh >/dev/null 2>&1
        sleep 5
fi

if [ -f $lockfile ] ; then
        $FIND $lockfile -type f -cmin +25 -print | $XARGS $REM >/dev/null 2>&1
        #echo "Swatch cronjob is already running. Delaying execution for now.";
        exit
fi

$TOUCH $lockfile

if [ -f "/tmp/.swatch.lock" ]; then
        $FIND /tmp/.swatch.lock -type f -cmin +25 -print | $XARGS $REM >/dev/null 2>&1
        #echo "Swatch executeable is already running. Delaying execution for now.";
        exit
else
        $TOUCH /tmp/.swatch.lock
        #echo "Running Swatch"
        /usr/sbin/swatch -c /etc/swatch.conf
        $REM -f /tmp/.swatch.lock
fi

# Run hotfix script:
/usr/sausalito/sbin/hotfixes.sh

$REM -f $lockfile
exit
