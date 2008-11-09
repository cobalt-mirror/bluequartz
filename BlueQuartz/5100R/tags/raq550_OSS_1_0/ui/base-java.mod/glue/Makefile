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

PROFILEDIR = $(PREFIX)/etc/profile.d
PROFILES=java.sh

SCRIPTS=java_load_war.pl

all:

install:
	echo "making dir:"
	echo $(CCESBINDIR)
	mkdir -p $(CCESBINDIR)
	mkdir -p $(PROFILEDIR)
	(cd sbin; \
	$(INSTALL) $(INSTALL_BINFLAGS) $(SCRIPTS) $(CCESBINDIR) )
	(cd etc/profile.d; $(INSTALL) $(PROFILES) $(PROFILEDIR); )

rpm:
