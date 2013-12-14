# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=alpine

VERSION=0.2
RELEASE=1BX03

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
#BUILDSRC=yes

#REQUIRES = base-alpine-locale-ja_JP, base-am-locale-ja_JP, base-apache-bandwidth-locale-ja_JP, base-apache-locale-ja_JP, base-backupcontrol-locale-ja_JP, base-console-locale-ja_JP, base-disk-locale-ja_JP, base-dns-locale-ja_JP, base-documentation-locale-ja_JP, base-email-locale-ja_JP, base-ftp-locale-ja_JP, base-import-locale-ja_JP, base-java-locale-ja_JP, base-subdomains-locale-ja_JP, base-mailman-locale-ja_JP, base-memory-locale-ja_JP, base-mysql-locale-ja_JP, base-network-locale-ja_JP, base-phpmyadmin-locale-ja_JP, base-power-locale-ja_JP, base-sauce-basic-locale-ja_JP, base-schedule-locale-ja_JP, base-services-locale-ja_JP, base-shell-locale-ja_JP, base-sitestats-locale-ja_JP, base-snmp-locale-ja_JP, base-ssh-locale-ja_JP, base-ssl-locale-ja_JP, base-swupdate-locale-ja_JP, base-system-locale-ja_JP, base-telnet-locale-ja_JP, base-time-locale-ja_JP, base-user-locale-ja_JP, base-vsite-locale-ja_JP, base-wizard-locale-ja_JP, base-remote-locale-ja_JP, sausalito-gallery-locale-ja_JP, base-raid-locale-ja_JP, ipa-gothic-fonts, ipa-mincho-fonts, ipa-pgothic-fonts, ipa-pmincho-fonts, man-pages-ja, vlgothic-fonts, vlgothic-p-fonts, fontpackages-filesystem, vlgothic-fonts-common

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

