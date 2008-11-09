# $Id: Makefile 3 2003-07-17 15:19:15Z will $
# Makefile to whip some files into /etc
#

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

INSTALL = /usr/bin/install -m 0644 -o root 
PWD = $(shell pwd)

install: install_etc 

install_etc: 
	mkdir -p $(PREFIX)/etc/
	$(INSTALL) etc/cobaltdns.DION $(PREFIX)/etc/
	$(INSTALL) etc/cobaltdns.OCN $(PREFIX)/etc/
	$(INSTALL) etc/cobaltdns.RFC2317 $(PREFIX)/etc/
