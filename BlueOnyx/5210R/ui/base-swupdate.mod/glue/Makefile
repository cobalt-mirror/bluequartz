ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif

# include defines.mk here before checking to see which target make is doing
include $(CCETOPDIR)/devel/defines.mk

# check PREFIX, because if it is defined we are 'make rpm' and CCETOPDIR
# needs to be redefined appropriately
ifdef PREFIX
CCETOPDIR = $(PREFIX)/usr/sausalito
endif

SCRIPTS=pkg_install.pl pkg_prepare.pl grab_updates.pl pkg_uninstall.pl swupdate_tmp_clean.pl rpm-clean-duplicates.pl locale-analyze.pl
PERLLIB=SWUpdate.pm
AMSCRIPTS=am/am_updates.pl

all:

install:
	mkdir -p $(CCESBINDIR)
	mkdir -p $(CCEPERLDIR)
	mkdir -p $(PREFIX)/etc/cron.d
	mkdir -p $(PREFIX)/etc/cron.daily
	mkdir -p $(PREFIX)/etc/yum/pluginconf.d
	mkdir -p $(PREFIX)/usr/lib/yum-plugins
	mkdir -p $(PREFIX)/root/.gnupg
	mkdir -p $(PREFIX)/usr/sausalito/yumcce
	mkdir -p $(SWATCHBINDIR)
	mkdir -p $(SWATCHCONFDIR)/base/swupdate
	mkdir -p $(PREFIX)/usr/sausalito/ui/web/.adm/images

	(cd sbin; \
	$(INSTALL) $(INSTALL_SBINFLAGS) $(SCRIPTS) $(CCESBINDIR); \
	$(INSTALL) $(INSTALL_LIBFLAGS) $(PERLLIB) $(CCEPERLDIR))
	install -m 0644 etc/cron.d/swupdate_tmp_clean.cron $(PREFIX)/etc/cron.d/swupdate_tmp_clean.cron
	install -m 0755 etc/cron.daily/rsscache_cron.pl $(PREFIX)/etc/cron.daily/rsscache_cron.pl
	cd gnupg; tar cBf - . | (cd $(PREFIX)/root/.gnupg; tar xBf -)
	install -m 0644 etc/yum/pluginconf.d/blueonyx.conf $(PREFIX)/etc/yum/pluginconf.d/blueonyx.conf
	install -m 0644 yum-plugins/blueonyx.py $(PREFIX)/usr/lib/yum-plugins/blueonyx.py
	install -m 0700 sbin/cced_yum $(CCESBINDIR)/cced_yum
	install -m 0700 sbin/pkgCleanup.pl $(CCESBINDIR)/pkgCleanup.pl
	install -m 0644 paypal/btn_donateCC_LG.gif $(PREFIX)/usr/sausalito/ui/web/.adm/images/btn_donateCC_LG.gif
	for file in $(AMSCRIPTS); do \
		$(INSTALL) $(INSTALL_BINFLAGS) $$file $(SWATCHBINDIR); \
	done
rpm:
