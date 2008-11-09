BUILD=20080101
PRODUCT=$(shell \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} centos-release`" = "5" ]; then \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "i386" ]; then echo '5200'; fi; \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "x86_64" ]; then echo '5201'; fi; \
fi; \
)

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
	tar cBf - --exclude '*CVS*' --exclude '.svn' etc | (cd $(PREFIX)/ ; tar xBf -)
	cat etc/build | sed -e 's/XX_BUILD_XX/$(BUILD)/' | \
			sed -e 's/XX_PRODUCT_XX/$(PRODUCT)/g' > $(PREFIX)/etc/build
	tar cBf - --exclude '*CVS*' --exclude '.svn' triggers | (cd $(PREFIX)/usr/sausalito/ ; tar xBf -)
	mkdir -p $(PREFIX)/var/www/html
	install -m 0644 httpd/index.html $(PREFIX)/var/www/html/index.html
	tar cBf - --exclude '*CVS*' --exclude '.svn' sbin | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	tar cBf - --exclude '*CVS*' --exclude '.svn' scripts | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	tar cBf - --exclude '*CVS*' --exclude '.svn' configs | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
