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

SCRIPTS = sbin/delayRestartCce.sh

all:

install:
	mkdir -p $(PREFIX)/usr/sausalito/sbin
	mkdir -p $(PREFIX)/etc/cron.weekly
	for file in $(SCRIPTS); do \
		$(INSTALL) $(INSTALL_BINFLAGS) $$file $(CCESBINDIR); \
	done
	install -m 0755 cron.weekly/restart_cce.cron $(PREFIX)/etc/cron.weekly

rpm:
