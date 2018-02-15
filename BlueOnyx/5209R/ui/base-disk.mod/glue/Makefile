ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif

# include defines.mk here before checking to see which target make is doing
include $(CCETOPDIR)/devel/defines.mk

SBIN_SCRIPTS = sbin/disk_restorequotas.pl

all:

.PHONY: $(SBIN_SCRIPTS)
$(SBIN_SCRIPTS)::
	mkdir -p $(CCESBINDIR)
	$(INSTALL) $(INSTALL_SBINFLAGS) $@ $(CCESBINDIR)

install: $(SBIN_SCRIPTS)
	mkdir -p $(PREFIX)/etc/cron.d/
	$(INSTALL) $(INSTALL_HEADERFLAGS) etc/cron.d/amdisk.cron $(PREFIX)/etc/cron.d/

rpm:
	
