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
SCRIPTS = usr/local/sbin/vacation.pl

all:

install:
	mkdir -p $(PREFIX)/usr/local/sbin/
	mkdir -p $(PREFIX)/etc/smrsh/
	mkdir -p $(PREFIX)/etc/cron.d
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	usr/local/sbin/vacation.pl $(PREFIX)/usr/local/sbin/
	cd $(PREFIX)/etc/smrsh; \
	ln -s /usr/local/sbin/vacation.pl vacation.pl
	mkdir -p $(PREFIX)/usr/sausalito/bin
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) bin/remove_domain.pl $(PREFIX)/usr/sausalito/bin/remove_domain.pl
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) bin/findalias.pl $(PREFIX)/usr/sausalito/bin/findalias.pl
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	usr/local/sbin/break-smrsh-loop.pl $(PREFIX)/usr/local/sbin/
	$(INSTALL) \
	etc/cron.d/break-smrsh-loop.cron $(PREFIX)/etc/cron.d
rpm:
	
