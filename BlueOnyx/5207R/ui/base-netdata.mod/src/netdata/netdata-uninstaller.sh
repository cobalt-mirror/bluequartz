#!/usr/bin/env bash

# this script will uninstall netdata

if [ "$1" != "--force" ]
    then
    echo >&2 "This script will REMOVE netdata from your system."
    echo >&2 "Run it again with --force to do it."
    exit 1
fi

echo >&2 "Stopping a possibly running netdata..."
for p in $(pidof netdata); do kill $p; done
sleep 2

deletedir() {
    if [ ! -z "$1" -a -d "$1" ]
        then
        echo
        echo "Deleting directory '$1' ..."
        rm -I -R "$1"
    fi
}

if [ ! -z "" -a -d "" ]
    then
    # installation prefix was given

    deletedir ""

else
    # installation prefix was NOT given

    if [ -f "/usr/sbin/netdata" ]
        then
        echo "Deleting /usr/sbin/netdata ..."
        rm -i "/usr/sbin/netdata"
    fi

    deletedir "/etc/netdata"
    deletedir "/usr/share/netdata"
    deletedir "/usr/libexec/netdata"
    deletedir "/var/lib/netdata"
    deletedir "/var/cache/netdata"
    deletedir "/var/log/netdata"
fi

if [ -f /etc/logrotate.d/netdata ]
    then
    echo "Deleting /etc/logrotate.d/netdata ..."
    rm -i /etc/logrotate.d/netdata
fi

if [ -f /etc/systemd/system/netdata.service ]
    then
    echo "Deleting /etc/systemd/system/netdata.service ..."
    rm -i /etc/systemd/system/netdata.service
fi

if [ -f /etc/init.d/netdata ]
    then
    echo "Deleting /etc/init.d/netdata ..."
    rm -i /etc/init.d/netdata
fi

getent passwd netdata > /dev/null
if [ 0 -eq 0 ]
    then
    echo
    echo "You may also want to remove the user netdata"
    echo "by running:"
    echo "   userdel netdata"
fi

getent group netdata > /dev/null
if [ 0 -eq 0 ]
    then
    echo
    echo "You may also want to remove the group netdata"
    echo "by running:"
    echo "   groupdel netdata"
fi

getent group docker > /dev/null
if [ 0 -eq 0 -a "0" = "1" ]
    then
    echo
    echo "You may also want to remove the netdata user from the docker group"
    echo "by running:"
    echo "   gpasswd -d netdata docker"
fi

