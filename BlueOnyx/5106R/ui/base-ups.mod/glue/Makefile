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
SCRIPTS = usr/bin/ups-emailstatus usr/bin/ups-shutdown usr/bin/ups-wakeslaves

all:

install:
	mkdir -p $(PREFIX)/usr/bin
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	usr/bin/ups-emailstatus $(PREFIX)/usr/bin/
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	usr/bin/ups-shutdown $(PREFIX)/usr/bin/
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
	usr/bin/ups-wakeslaves $(PREFIX)/usr/bin/
rpm:
