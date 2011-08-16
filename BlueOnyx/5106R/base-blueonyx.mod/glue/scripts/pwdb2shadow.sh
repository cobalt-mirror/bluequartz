#!/bin/sh
# $Id: $
# Copyright 2008 Project BlueOnyx, All rights reserved.
# Author : Hisao SHIBUYA <shibuya@bluequartz.org>

# Source sysconfig
if [ -f /etc/sysconfig/blueonyx ]; then
	. /etc/sysconfig/blueonyx
fi

if [ $AUTH == 'shadow' ]; then
	exit 0;
fi

if [ -f /var/db/passwd.db ]; then
	# Backup db and shadow files
	if [ ! -d /tmp/pwdb2shadow.backup ]; then
		/bin/mkdir /tmp/pwdb2shadow.backup
	fi
	/bin/cp -p /var/db/*.db /tmp/pwdb2shadow.backup
	/bin/cp -p /etc/passwd /etc/shadow /etc/group /tmp/pwdb2shadow.backup

	/usr/sausalito/sbin/pwdb2shadow.pl
	if [ $? != 0 ]; then
		initlog -n pwdb2shadow -s 'failed to convert from pwdb to shadow'
		exit 1;
	else
		/bin/rm -f /var/db/*.db
		initlog -n pwdb2shadow -s 'successfull to convert from pwdb to shadow'
	fi
fi

# modify pam configucation
/usr/bin/perl -pi -e "s|pam_pwdb\.so|pam_unix\.so|g" /etc/pam.d/system-auth

# remove db settings from /etc/nsswitch.conf
/usr/sausalito/sbin/disablePwdb.pl

# modify sysconfig for BlueOnyx to use shadow
perl -pi -e 's|AUTH=pwdb|AUTH=shadow|g' /etc/sysconfig/blueonyx

# Convert SASLAUTHd:
/bin/sed -i -e 's@MECH=pam@MECH=shadow@' /etc/sysconfig/saslauthd

exit 0;
