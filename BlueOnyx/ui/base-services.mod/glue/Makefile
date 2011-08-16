all:

clean:

install:
	-mkdir -p $(PREFIX)/usr/local/sbin
	-mkdir -p $(PREFIX)/usr/sausalito/sbin
	-mkdir -p $(PREFIX)/etc/rc.d/init.d
	-mkdir -p $(PREFIX)/etc/pam.d
	-mkdir -p $(PREFIX)/etc/cobalt/whitebox.d
	-find ./etc ./usr -name CVS -prune -or -type d -exec mkdir $(PREFIX)/\{} \; -print
	-find ./etc ./usr -name CVS -prune -or -type f -exec install -o root -g root \{} $(PREFIX)/\{} \; -print
	chmod a+x $(PREFIX)/etc/rc.d/init.d/*
	chmod g-w $(PREFIX)/etc/rc.d/init.d/*
	chmod -R 700 $(PREFIX)/etc/cobalt/whitebox.d
	-mkdir -p $(PREFIX)/usr/sbin
	ln -s /etc/rc.d/init.d $(PREFIX)/etc/init.d
