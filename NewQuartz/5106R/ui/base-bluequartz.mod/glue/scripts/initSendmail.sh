#!/bin/sh

CONFDIR='/usr/sausalito/configs/sendmail'
if [ ! -f /etc/mail/aliases ]; then
  if [ -f /etc/mail/aliases.rpmsave ]; then
    mv /etc/mail/aliases.rpmsave /etc/mail/aliases
  else
    ln -s /etc/aliases /etc/mail/aliases
  fi
  grep '^root:' /etc/mail/aliases > /dev/null 2>&1
  if [ $? = 1 ]; then
    echo 'root:		admin' >> /etc/mail/aliases
  fi
  /usr/sbin/makemap hash /etc/mail/aliases.db < /etc/mail/aliases
fi
cp -p $CONFDIR/sendmail.mc /etc/mail/sendmail.mc
cp -p $CONFDIR/popauth.m4 /usr/share/sendmail-cf/hack/popauth.m4

m4 /usr/share/sendmail-cf/m4/cf.m4 /etc/mail/sendmail.mc > /etc/mail/sendmail.cf

touch /etc/mail/virthosts
chmod 0600 /etc/mail/virthosts
chown root.root /etc/mail/virthosts

touch /var/log/mail/statistics
