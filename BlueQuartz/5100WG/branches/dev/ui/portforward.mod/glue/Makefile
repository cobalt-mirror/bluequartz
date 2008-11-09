# $id:$
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

all:

install:
	mkdir -p $(PREFIX)/etc/rc.d/init.d/
	mkdir -p $(PREFIX)/etc/rc.d/rc3.d/
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) \
		init.d/portfw $(PREFIX)/etc/rc.d/init.d/
	test -e $(PREFIX)/etc/rc.d/rc3.d/S98portfw || ln -s ../init.d/portfw \
		$(PREFIX)/etc/rc.d/rc3.d/S98portfw

rpm:
	
