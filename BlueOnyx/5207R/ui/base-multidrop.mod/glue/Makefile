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
SCRIPTS = etc/init.d/fetchmail

all:

install:
	mkdir -p $(PREFIX)/etc/init.d/
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	etc/init.d/fetchmail $(PREFIX)/etc/init.d/

rpm:
