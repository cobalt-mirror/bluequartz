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
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	usr/local/sbin/vacation.pl $(PREFIX)/usr/local/sbin/

rpm:
	
