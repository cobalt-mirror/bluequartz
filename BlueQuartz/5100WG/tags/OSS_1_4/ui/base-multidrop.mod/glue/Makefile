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
SCRIPTS = etc/rc.d/init.d/fetchmail

all:

install:
	mkdir -p $(PREFIX)/etc/rc.d/init.d/
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	etc/rc.d/init.d/fetchmail $(PREFIX)/etc/rc.d/init.d/

rpm:
