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

SCRIPTS=install-printer.pl

all:

install:
	mkdir -p $(CCESBINDIR)
	(cd sbin; \
	$(INSTALL) $(INSTALL_SBINFLAGS) $(SCRIPTS) $(CCESBINDIR); )

rpm:
