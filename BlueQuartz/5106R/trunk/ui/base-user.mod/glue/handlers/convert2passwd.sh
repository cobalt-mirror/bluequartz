#!/bin/bash
# Author: Brian N. Smith
# Copyright 2007, NuOnce Networks, Inc.  All rights reserved.
# $Id: convert2passwd.sh, v1.00 2007/12/14 09:12:00 bsmith Exp $

LOGFILE=/tmp/convert2passwd.log
BACKUP_DIR=/tmp/conversion_backups
SERVICE_STOP="crond httpd xinetd dovecot sendmail dbrecover"
SERVICE_START="dbrecover xinetd dovecot sendmail httpd crond"
FORCE_KILL="sendmail"

[ -e $LOGFILE ]; /bin/rm -f $LOGFILE
[ ! -e $BACKUP_DIR ]; /bin/mkdir -p $BACKUP_DIR

if [ ! -e /var/db ]; then
  /bin/echo "FAILED";
  /bin/echo "/var/db does not exist" >> $LOGFILE
  exit 1;
fi

for SERVICE in $SERVICE_STOP; do
  /sbin/service $SERVICE stop >/dev/null 2>&1
done

for SERVICE in $FORCE_KILL; do
  /usr/bin/killall -9 $SERVICE >/dev/null 2>&1
done

/bin/echo "Backups are stored in: $BACKUP_DIR" >> $LOGFILE
/bin/tar cfpz $BACKUP_DIR/var-db.tgz /var/db/* >/dev/null 2>&1
/bin/cp -p /etc/passwd $BACKUP_DIR/ >/dev/null 2>&1
/bin/cp -p /etc/shadow $BACKUP_DIR/ >/dev/null 2>&1
/bin/cp -p /etc/group $BACKUP_DIR/ >/dev/null 2>&1
/bin/cp -p /etc/pam.d/system-auth $BACKUP_DIR/ >/dev/null 2>&1
/bin/cp -p /etc/sysconfig/saslauth $BACKUP_DIR/ >/dev/null 2>&1
/bin/cp -p /etc/nsswitch.conf $BACKUP_DIR/ >/dev/null 2>&1

/bin/rm -f /var/db/log.*
/bin/rm -f /var/db/__db*

/usr/bin/makedb -u /var/db/passwd.db | /bin/grep -v "^=" | /usr/bin/perl -p -e "s/(^\..*?) //" >> /etc/passwd 
/usr/bin/makedb -u /var/db/shadow.db | /usr/bin/perl -p -e "s/(^\..*?) //" >> /etc/shadow 
/usr/bin/makedb -u /var/db/group.db  | /bin/grep -v "^=" | /usr/bin/perl -p -e "s/(^\..*?) //" >> /etc/group 

/bin/touch /var/db/passwd /var/db/shadow /var/db/group >/dev/null 2>&1
/usr/bin/makedb -o /var/db/passwd.db /var/db/passwd >/dev/null 2>&1
/usr/bin/makedb -o /var/db/shadow.db /var/db/shadow >/dev/null 2>&1
/usr/bin/makedb -o /var/db/group.db /var/db/group >/dev/null 2>&1
/bin/rm -f /var/db/passwd /var/db/shadow /var/db/group >/dev/null 2>&1

/usr/bin/perl -pi -e "s#^MECH=pam#MECH=shadow#" /etc/sysconfig/saslauthd
/usr/bin/perl -pi -e "s#db files#files#" /etc/nsswitch.conf 

#/bin/cp -p /usr/sausalito/handlers/base/user/system-auth-centos4 /etc/pam.d/system-auth >/dev/null 2>&1
/bin/cp -p /usr/sausalito/handlers/base/user/system-auth-centos5 /etc/pam.d/system-auth >/dev/null 2>&1
/bin/cp -p /usr/sausalito/handlers/base/user/system-auth-centos5 /etc/pam.d/system-auth-cce >/dev/null 2>&1
/usr/bin/authconfig --enableshadow --useshadow --updateall  >/dev/null 2>&1

for SERVICE in $SERVICE_START; do
  /sbin/service $SERVICE start >/dev/null 2>&1
done

exit 0
