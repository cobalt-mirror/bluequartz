BUILD=20140205
PRODUCT=$(shell \
if [ -f /etc/fedora-release ] && [ "`rpm -q --qf=%{version} fedora-release`" = "1" ]; then echo '5100'; fi; \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} centos-release`" = "3" ]; then echo '5101'; fi; \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} centos-release`" = "4" ]; then \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "i386" ]; then echo '5102'; fi; \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "x86_64" ]; then echo '5103'; fi; \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "ppc" ]; then echo '5104'; fi; \
  if [ "`rpm -q --qf=%{arch} centos-release`" = "alpha" ]; then echo '5105'; fi; \
fi; \
if [ -f /etc/redhat-release ] && [ "`rpm -q --qf=%{version} centos-release`" = "5" ]; then echo '5106'; fi; \
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
	mkdir -p $(PREFIX)/usr/share/doc/blueonyx-release-5106R
	tar cBf - --exclude '.svn' etc | (cd $(PREFIX)/ ; tar xBf -)
	cat etc/build | sed -e 's/XX_BUILD_XX/$(BUILD)/' | \
			sed -e 's/XX_PRODUCT_XX/$(PRODUCT)/g' > $(PREFIX)/etc/build
	tar cBf - --exclude '.svn' triggers | (cd $(PREFIX)/usr/sausalito/ ; tar xBf -)
	mkdir -p $(PREFIX)/var/www/html
	install -m 0644 httpd/index.html $(PREFIX)/var/www/html/index.html
	tar cBf - --exclude '.svn' sbin | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	tar cBf - --exclude '.svn' scripts | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	tar cBf - --exclude '.svn' configs | (cd $(PREFIX)/usr/sausalito ; tar xBf -)
	install -m 0644 doc/* $(PREFIX)/usr/share/doc/blueonyx-release-5106R
