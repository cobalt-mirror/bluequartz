ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif

# include defines.mk here before checking to see which target make is doing
include $(CCETOPDIR)/devel/defines.mk

all:

install:
	mkdir -p $(PREFIX)/etc/cron.d/
	$(INSTALL) $(INSTALL_HEADERFLAGS) etc/cron.d/quotastats.cron $(PREFIX)/etc/cron.d/

rpm:
	
