#!/bin/sh

mkdir -p /home/spool

mv -f /var/spool/mail /home/spool/mail
ln -snf /home/spool/mail /var/spool/mail
