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

# Handle Mailman presence:
if [ -f /etc/rc.d/init.d/mailman ]; then
	cp -p $CONFDIR/sendmail.mc.mailman /etc/mail/sendmail.mc
else
	touch /tmp/nolist.mailmain
fi

# Handle Majordomo presence:
if [ -f /usr/local/majordomo/bin/approve ]; then
	cp -p $CONFDIR/sendmail.mc.majordomo /etc/mail/sendmail.mc
else
	touch /tmp/nolist.majordomo
fi

# Handle case where we have neither and a stock Sendmail config:
/bin/grep 'setup for BlueOnyx' /etc/mail/sendmail.mc > /dev/null 2>&1
if [ $? = 0 ]; then 
	if [ ! -f /tmp/nolist.mailmain ] && [ ! -f /tmp/nolist.majordomo ];then
		cp -p $CONFDIR/sendmail.mc /etc/mail/sendmail.mc
	fi
fi

cp -p $CONFDIR/popauth.m4 /usr/share/sendmail-cf/hack/popauth.m4

if [ -f /usr/sausalito/constructor/solarspeed/av_spam/aa_initial_inst.pl ];then
	/usr/sausalito/constructor/solarspeed/av_spam/aa_initial_inst.pl
fi

m4 /usr/share/sendmail-cf/m4/cf.m4 /etc/mail/sendmail.mc > /etc/mail/sendmail.cf

touch /etc/mail/virthosts
chmod 0600 /etc/mail/virthosts
chown root.root /etc/mail/virthosts

touch /var/log/mail/statistics
