#!/bin/sh

if [ ! -f /etc/postfix/access.db ]; then
  /usr/sbin/postmap hash:/etc/postfix/access > /dev/null 2>&1
fi

if [ ! -f /etc/postfix/transport.db ]; then
  /usr/sbin/postmap hash:/etc/postfix/transport > /dev/null 2>&1
fi

if [ ! -f /etc/postfix/virtual.db ]; then
  /usr/sbin/postmap hash:/etc/postfix/virtual > /dev/null 2>&1
fi

if [ ! -f /etc/aliases.db ]; then
  /usr/sbin/postmap hash:/etc/aliases > /dev/null 2>&1
fi

if [ ! -f /etc/aliases.majordomo.db ]; then
  /usr/sbin/postmap hash:/etc/aliases.majordomo > /dev/null 2>&1
fi

