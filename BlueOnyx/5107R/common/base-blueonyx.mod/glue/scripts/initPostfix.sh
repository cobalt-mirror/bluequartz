#!/bin/sh

target=/etc/postfix/master.cf
tmpfile=$target.temp
cat $target | \
  sed -e '/^#  -o/d' \
  > $tmpfile
if test -s $tmpfile
then
  cp -f $tmpfile $target
fi
rm -f $tmpfile

if [ ! -f /etc/postfix/local-host-names ]; then
  touch /etc/postfix/local-host-names
fi

if [ ! -f /etc/postfix/transport ]; then
  echo "" > /etc/postfix/transport
fi

grep 'mech_list' /usr/lib/sasl2/smtpd.conf > /dev/null 2>&1
if [ $? = 1 ]; then
  echo 'mech_list: PLAIN LOGIN' >> /usr/lib/sasl2/smtpd.conf
fi
