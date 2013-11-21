#!/bin/sh
# Original Author: Brian N. Smith

# Fix Zone Transfer Problem.
/bin/chmod 770 /var/named/chroot/var/named/

# Lets add symlink for old style dns setup
/bin/ln -s /var/named/chroot/var/named /etc/named
/bin/ln -s /var/named/chroot/etc/named.conf /etc/named.conf

# add -4 option
grep "^OPTIONS" /etc/sysconfig/named > /dev/null 2>&1
if [ $? = 1 ]; then
  echo 'OPTIONS="-4"' >> /etc/sysconfig/named
fi
