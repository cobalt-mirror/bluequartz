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

SCRIPTS=pkg_install.pl pkg_prepare.pl grab_updates.pl pkg_uninstall.pl
PERLLIB=SWUpdate.pm

all:

install:
	mkdir -p $(CCESBINDIR)
	mkdir -p $(CCEPERLDIR)
	mkdir -p $(PREFIX)/root/.gnupg
	(cd sbin; \
	$(INSTALL) $(INSTALL_SBINFLAGS) $(SCRIPTS) $(CCESBINDIR); \
	$(INSTALL) $(INSTALL_LIBFLAGS) $(PERLLIB) $(CCEPERLDIR))
	cd gnupg; tar cBf - . | (cd $(PREFIX)/root/.gnupg; tar xBf -)

rpm:
