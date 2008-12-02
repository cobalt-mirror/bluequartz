ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif

include $(CCETOPDIR)/devel/defines.mk

SBIN_SCRIPTS = sbin/cce_lock.pl

install:
	mkdir -p $(CCESBINDIR)
	for file in $(SBIN_SCRIPTS); do \
		$(INSTALL) $(INSTALL_SBINFLAGS) $$file $(CCESBINDIR); \
	done
