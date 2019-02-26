all:

clean:

install:
	find ./etc -name CVS -prune -or -type d -exec mkdir -p $(PREFIX)/\{} \; -print
	find ./etc -name CVS -prune -or -type f -exec install -o root -g root \{} $(PREFIX)/\{} \; -print
	mkdir -p $(PREFIX)/etc/logrotate.d
	install ./etc/logrotate.d/admserv $(PREFIX)/etc/logrotate.d
	mkdir -p $(PREFIX)/usr/sbin
	mkdir -p $(PREFIX)/etc/admserv/certs
	install -d -m 700 $(PREFIX)/var/log/admserv
	install -d -m 700 $(PREFIX)/var/cache/admserv
	ln -s httpd $(PREFIX)/usr/sbin/httpd.admsrv
	ln -s ../../var/log/admserv $(PREFIX)/etc/admserv/logs
	@if [ -d /usr/lib/httpd/modules ]; then \
		ln -s ../../usr/lib/httpd/modules $(PREFIX)/etc/admserv/modules; \
	elif [ -d /usr/lib64/httpd/modules ]; then \
		ln -s ../../usr/lib64/httpd/modules $(PREFIX)/etc/admserv/modules; \
	fi
	ln -s ../../var/run $(PREFIX)/etc/admserv/run
	mkdir -p $(PREFIX)/usr/lib/systemd/system/
	install ./systemd/admserv.service $(PREFIX)/usr/lib/systemd/system/