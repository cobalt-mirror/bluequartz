# Silly makefile

all:
	
	
install:
	if [ ! -d $(PREFIX)/etc/httpd/conf/figlets ]; then \
		mkdir -p $(PREFIX)/etc/httpd/conf/figlets; \
	fi

	install -m 0755 conf_assemble $(PREFIX)/etc/httpd/conf
	install -m 0755 -d $(PREFIX)/etc/httpd/conf/figlets
	install -m 0744 \
		httpd.conf \
		srm.conf \
		access.conf \
	$(PREFIX)/etc/httpd/conf/

clean:
