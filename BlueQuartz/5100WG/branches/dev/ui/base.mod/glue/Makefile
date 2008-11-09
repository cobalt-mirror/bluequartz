all:

install:
	-mkdir -p $(PREFIX)/etc/pam.d
	-mkdir -p $(PREFIX)/etc/rc.d/init.d
	-mkdir -p $(PREFIX)/etc/cron.quarter-hourly
	-mkdir -p $(PREFIX)/etc/logrotate.d
	-tar cBf - --exclude '*CVS*' . | (cd $(PREFIX)/ ; tar xBf -)
	mkdir -p $(PREFIX)/usr/sausalito/sbin
	install -m 0755 sbin/selectLanguage $(PREFIX)/usr/sausalito/sbin/selectLanguage
