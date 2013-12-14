# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=alpine

VERSION=0.1
RELEASE=85BX37

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
#BUILDSRC=yes

REQUIRES = base-alpine-locale-ja_JP, base-am-locale-ja_JP, base-apache-bandwidth-locale-ja_JP, base-apache-locale-ja_JP, base-backupcontrol-locale-ja_JP, base-console-locale-ja_JP, base-disk-locale-ja_JP, base-dns-locale-ja_JP, base-documentation-locale-ja_JP, base-email-locale-ja_JP, base-ftp-locale-ja_JP, base-import-locale-ja_JP, base-java-locale-ja_JP, base-subdomains-locale-ja_JP, base-memory-locale-ja_JP, base-mysql-locale-ja_JP, base-network-locale-ja_JP, base-phpmyadmin-locale-ja_JP, base-power-locale-ja_JP, base-sauce-basic-locale-ja_JP, base-schedule-locale-ja_JP, base-services-locale-ja_JP, base-shell-locale-ja_JP, base-sitestats-locale-ja_JP, base-snmp-locale-ja_JP, base-ssh-locale-ja_JP, base-ssl-locale-ja_JP, base-swupdate-locale-ja_JP, base-system-locale-ja_JP, base-telnet-locale-ja_JP, base-time-locale-ja_JP, base-user-locale-ja_JP, base-vsite-locale-ja_JP, base-wizard-locale-ja_JP, base-remote-locale-ja_JP, sausalito-gallery-locale-ja_JP, base-raid-locale-ja_JP, anthy, fonts-japanese, kcc, lv, man-pages-ja, nkf, perl-NKF, base-alpine-locale-en_US, base-am-locale-en_US, base-apache-bandwidth-locale-en_US, base-apache-locale-en_US, base-backupcontrol-locale-en_US, base-console-locale-en_US, base-disk-locale-en_US, base-dns-locale-en_US, base-documentation-locale-en_US, base-email-locale-en_US, base-ftp-locale-en_US, base-import-locale-en_US, base-java-locale-en_US, base-memory-locale-en_US, base-mysql-locale-en_US, base-network-locale-en_US, base-phpmyadmin-locale-en_US, base-phpsysinfo-locale-en_US, base-power-locale-en_US, base-remote-locale-en_US, base-sauce-basic-locale-en_US, base-schedule-locale-en_US, base-services-locale-en_US, base-shell-locale-en_US, base-sitestats-locale-en_US, base-snmp-locale-en_US, base-ssh-locale-en_US, base-ssl-locale-en_US, base-subdomains-locale-en_US, base-swupdate-locale-en_US, base-system-locale-en_US, base-telnet-locale-en_US, base-time-locale-en_US, base-user-locale-en_US, base-vsite-locale-en_US, base-wizard-locale-en_US

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
include /usr/sausalito/devel/defines.mk

# where do the manuals live
WEBROOT=$(CCEDIR)/ui/web

# defines for stuff that ends up in /etc
ETCDIR=$(PREFIX)/etc
SYSCONFIG=$(ETCDIR)/sysconfig
CONFIG_DIR=etc/sysconfig
CONFIG_FILES=firstboot

MKDIR=mkdir -p

remove:
	rm -rf /usr/sausalito/ui/web/base/alpine
	rm -rf /usr/sausalito/ui/menu/base/alpine

install: install_perl install_config
	$(MKDIR) $(CCEDIR)/ui/web
	for locale in $(XLOCALEPAT); do \
		exclude="$$exclude -and -not -path *\.$$locale"; \
	done; \
	find web -type f -not -path "*/CVS*" $$exclude -exec install -D -o root -g root -m 644 {} $(CCEDIR)/ui/{} \;
	$(MKDIR) $(CCEDIR)/ui/web/libImage
	find libImage -type f -not -path "*/CVS*" $$exclude -exec install -D -o root -g root -m 644 {} $(CCEDIR)/ui/web/{} \;
	find manuals -type f -not -path "*CVS*" -exec install {} -D -o root -g root -m 644 $(WEBROOT)/{} \;
	include="-false"; \
	for locale in $(LOCALES); do \
		include="$$include -or -path *\.$$locale"; \
	done; \
	FLIST=`find web/error -follow -type f $$include \
		2>/dev/null | grep -v CVS | grep -v '/\.'`; \
	for file in $$FLIST; do \
		isfallback=`echo $$file | grep '.$(DEFLOCALE)$$'`; \
		if [ x"$$isfallback" != x"" ]; then \
			name=`basename $$file .$(DEFLOCALE)`; \
			if [ ! -f $(CCEDIR)/ui/web/error/$$name ]; then \
			`ln -s $$name.$(DEFLOCALE) \
				$(CCEDIR)/ui/web/error/$$name`; \
			fi; \
		fi; \
	done;

install_config:
	@$(MKDIR) $(SYSCONFIG)
	@for file in $(CONFIG_FILES); do \
		echo "installing config file: $$file"; \
		$(INSTALL_OTH) -o root -g root $(CONFIG_DIR)/$$file \
			 $(SYSCONFIG)/$$file; \
	done

