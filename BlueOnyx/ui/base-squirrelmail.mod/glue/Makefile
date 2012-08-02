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

MODADMSERVDIR = $(PREFIX)/etc/admserv/conf.d
MODADMSERVFILE=squirrelmail.conf
MODHTTPDDIR = $(PREFIX)/etc/httpd/conf.d
MODHTTPDFILE=squirrelmail.conf.bx

all:

install:
	echo "making dir:"
	echo $(CCESBINDIR)
	mkdir -p $(MODADMSERVDIR)
	(cd etc/admserv/conf.d; $(INSTALL) $(MODADMSERVFILE) $(MODADMSERVDIR); )
	mkdir -p $(MODHTTPDDIR)
	(cd etc/httpd/conf.d; $(INSTALL) $(MODHTTPDFILE) $(MODHTTPDDIR); )

rpm:
