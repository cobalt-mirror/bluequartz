#!/bin/sh
# Original Author: Brian N. Smith

# Fix Zone Transfer Problem.
/bin/chmod 770 /var/named/chroot/var/named/

# Lets add symlink for old style dns setup
/bin/ln -s /var/named/chroot/var/named /etc/named
/bin/ln -s /var/named/chroot/etc/named.conf /etc/named.conf
