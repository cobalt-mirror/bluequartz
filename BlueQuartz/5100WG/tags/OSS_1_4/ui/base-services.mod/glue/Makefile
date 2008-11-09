all:

clean:

install:
	-mkdir -p $(PREFIX)/usr/local/sbin
	-mkdir -p $(PREFIX)/etc/rc.d/init.d
	-mkdir -p $(PREFIX)/etc/pam.d
	-find ./etc ./usr -name CVS -prune -or -type d -exec mkdir $(PREFIX)/\{} \; -print
	-find ./etc ./usr -name CVS -prune -or -type f -exec install -o root -g root \{} $(PREFIX)/\{} \; -print
	chmod a+x $(PREFIX)/etc/rc.d/init.d/*
	-mkdir -p $(PREFIX)/usr/sbin
	ln -s /usr/sbin/httpd $(PREFIX)/usr/sbin/ahttpd
