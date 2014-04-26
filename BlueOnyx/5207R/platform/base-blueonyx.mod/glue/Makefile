BUILD=20140426
PRODUCT=$(shell if [ `uname -m` = "x86_64" ]; then  echo -n "5208"; else  echo -n "5207"; fi)

all:

clean:

install:
	mkdir -p $(PREFIX)/etc
	mkdir -p $(PREFIX)/etc/rc.d/init.d
	mkdir -p $(PREFIX)/etc/logrotate.d
	mkdir -p $(PREFIX)/etc/locks
	mkdir -p $(PREFIX)/usr/sausalito/sbin
	mkdir -p $(PREFIX)/usr/sausalito/scripts
	mkdir -p $(PREFIX)/usr/sausalito/runonce
	mkdir -p $(PREFIX)/usr/share/doc/blueonyx-release-520XR
	tar cBf - --exclude '.svn' etc | (cd $(PREFIX)/ ; tar xBf -)
	cat etc/build | sed -e 's/XX_BUILD_XX/$(BUILD)/' | \
			sed -e 's/XX_PRODUCT_XX/$(PRODUCT)/g' > $(PREFIX)/etc/build
	tar cBf - --exclude '.svn' triggers | (cd $(PREFIX)/usr/sausalito/ ; tar xBf -)
	mkdir -p $(PREFIX)/var/www/html
	install -m 0644 httpd/index.html $(PREFIX)/var/www/html/index.html
	tar cBf - --exclude '.svn' sbin | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	tar cBf - --exclude '.svn' scripts | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	tar cBf - --exclude '.svn' configs | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	install -m 0644 doc/* $(PREFIX)/usr/share/doc/blueonyx-release-520XR
