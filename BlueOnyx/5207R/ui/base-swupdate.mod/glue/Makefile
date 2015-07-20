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

SCRIPTS=pkg_install.pl pkg_prepare.pl grab_updates.pl pkg_uninstall.pl swupdate_tmp_clean.pl rpm-clean-duplicates.pl
PERLLIB=SWUpdate.pm

AMSCRIPTS=am/am_updates.pl

all:

install:
	mkdir -p $(CCESBINDIR)
	mkdir -p $(CCEPERLDIR)
	mkdir -p $(PREFIX)/etc/cron.d
	mkdir -p $(PREFIX)/root/.gnupg
	mkdir -p $(SWATCHBINDIR)
	mkdir -p $(SWATCHCONFDIR)/base/swupdate
	(cd sbin; \
	$(INSTALL) $(INSTALL_SBINFLAGS) $(SCRIPTS) $(CCESBINDIR); \
	$(INSTALL) $(INSTALL_LIBFLAGS) $(PERLLIB) $(CCEPERLDIR))
	install -m 0644 etc/cron.d/swupdate_tmp_clean.cron $(PREFIX)/etc/cron.d/swupdate_tmp_clean.cron
	cd gnupg; tar cBf - . | (cd $(PREFIX)/root/.gnupg; tar xBf -)
	for file in $(AMSCRIPTS); do \
		$(INSTALL) $(INSTALL_BINFLAGS) $$file $(SWATCHBINDIR); \
	done
rpm:
