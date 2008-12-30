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

MODAPACHEDIR = $(PREFIX)/etc/httpd/conf.d
MODAPACHEFILE=subdomains.conf

all:

install:
	echo "making dir:"
	echo $(CCESBINDIR)
	mkdir -p $(MODAPACHEDIR)
	(cd etc/httpd/conf.d; $(INSTALL) $(MODAPACHEFILE) $(MODAPACHEDIR); )

rpm:
	
