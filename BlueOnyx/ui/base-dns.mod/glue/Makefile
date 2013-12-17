# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Makefile to whip some files into /etc
#

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

SCRIPTS=named_enable.pl named_trigger_update.pl

INSTALL = /usr/bin/install -m 0644 -o root
PWD = $(shell pwd)

all:

install:
	mkdir -p $(PREFIX)/etc/
	mkdir -p $(CCESBINDIR)
	$(INSTALL) etc/cobaltdns.DION $(PREFIX)/etc/
	$(INSTALL) etc/cobaltdns.OCN-JT $(PREFIX)/etc/
	$(INSTALL) etc/cobaltdns.RFC2317 $(PREFIX)/etc/
	(cd sbin; $(INSTALL) $(INSTALL_BINFLAGS) $(SCRIPTS) $(CCESBINDIR) )

rpm:

