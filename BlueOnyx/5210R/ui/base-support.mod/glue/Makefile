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

SCRIPTS=sbin/sosreport-helper.pl sbin/sosreport.sh
AMBIN=am/am_support.pl

all:

install:
	mkdir -p $(CCESBINDIR)
	mkdir -p $(SWATCHBINDIR)
	mkdir -p $(SWATCHCONFDIR)/base/support
	for file in $(SCRIPTS); do \
	        $(INSTALL) $(INSTALL_BINFLAGS) $$file $(CCESBINDIR); \
	done
	for file in $(AMBIN); do \
	        $(INSTALL) $(INSTALL_BINFLAGS) $$file $(SWATCHBINDIR); \
	done
rpm:

