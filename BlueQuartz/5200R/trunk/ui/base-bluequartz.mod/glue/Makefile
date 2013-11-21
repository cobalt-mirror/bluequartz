BUILD=20110503
PRODUCT=$(shell \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} centos-release`" = "5" ]; then \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "i386" ]; then echo '5200'; fi; \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "x86_64" ]; then echo '5201'; fi; \
fi; \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} sl-release`" = "6.0" ]; then \
  if [ "`rpm -q --qf=%{arch} sl-release`" = "i686" ]; then echo '5202'; fi; \
  if [ "`rpm -q --qf=%{arch} sl-release`" = "x86_64" ]; then echo '5203'; fi; \
fi; \
)

all:

clean:

install:
	mkdir -p $(PREFIX)/etc
	mkdir -p $(PREFIX)/etc/rc.d/init.d
	mkdir -p $(PREFIX)/etc/logrotate.d
	mkdir -p $(PREFIX)/etc/locks
	mkdir -p $(PREFIX)/etc/sysconfig
	mkdir -p $(PREFIX)/usr/sausalito/sbin
	mkdir -p $(PREFIX)/usr/sausalito/scripts
	mkdir -p $(PREFIX)/usr/sausalito/runonce
	mkdir -p $(PREFIX)/usr/share/doc/bluequartz-release-5200R
	tar cBf - --exclude '*CVS*' --exclude '.svn' etc | (cd $(PREFIX)/ ; tar xBf -)
	cat etc/build | sed -e 's/XX_BUILD_XX/$(BUILD)/' | \
			sed -e 's/XX_PRODUCT_XX/$(PRODUCT)/g' > $(PREFIX)/etc/build
	tar cBf - --exclude '*CVS*' --exclude '.svn' triggers | (cd $(PREFIX)/usr/sausalito/ ; tar xBf -)
	mkdir -p $(PREFIX)/var/www/html
	install -m 0644 httpd/index.html $(PREFIX)/var/www/html/index.html
	tar cBf - --exclude '*CVS*' --exclude '.svn' sbin | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	tar cBf - --exclude '*CVS*' --exclude '.svn' scripts | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	tar cBf - --exclude '*CVS*' --exclude '.svn' configs | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	install -m 0644 doc/* $(PREFIX)/usr/share/doc/bluequartz-release-5200R
