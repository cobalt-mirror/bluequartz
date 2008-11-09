all:

install:
	-mkdir -p $(PREFIX)/etc/pam.d
	-mkdir -p $(PREFIX)/etc/rc.d/init.d
	-tar cBf - --exclude '*CVS*' . | (cd $(PREFIX)/ ; tar xBf -)
	-mkdir -p $(PREFIX)/usr/local/sbin
	install -m 0755 other/fibonacci $(PREFIX)/usr/local/sbin/split_logs
	install -m 0755 other/fibonacci $(PREFIX)/usr/local/sbin/maillog2commonlog.pl
	mkdir -p $(PREFIX)/usr/sausalito/sbin
	install -m 0755 sbin/selectLanguage $(PREFIX)/usr/sausalito/sbin/selectLanguage
