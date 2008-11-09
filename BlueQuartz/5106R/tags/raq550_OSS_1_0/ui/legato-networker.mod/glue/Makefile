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

# files to install
SCRIPTS = etc/rc.d/init.d/cobalt-networker nsr/res/cmd

all:

install:
	mkdir -p $(PREFIX)/nsr/res/
	mkdir -p $(PREFIX)/etc/rc.d/init.d/
	mkdir -p $(PREFIX)/usr/bin
	mkdir -p $(PREFIX)/usr/sausalito
	mkdir -p $(PREFIX)/var/lib/pgsql
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	nsr/res/cmd $(PREFIX)/nsr/res/
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	etc/rc.d/init.d/cobalt-networker $(PREFIX)/etc/rc.d/init.d/
	$(INSTALL) root/root.nsr $(PREFIX)/.nsr
	$(INSTALL) root/postgres.nsr $(PREFIX)/var/lib/pgsql/.nsr
	$(INSTALL) root/cce.nsr $(PREFIX)/usr/sausalito/.nsr
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) usr/bin/cceasm $(PREFIX)/usr/bin
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) usr/bin/postgresasm $(PREFIX)/usr/bin
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) usr/bin/postgresasm_restore $(PREFIX)/usr/bin
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) usr/bin/quotaasm $(PREFIX)/usr/bin
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) usr/bin/quotaasm_restore $(PREFIX)/usr/bin

rpm:
	
