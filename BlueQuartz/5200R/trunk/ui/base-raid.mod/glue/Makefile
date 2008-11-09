# $Id: Makefile 710 2006-03-09 16:20:40Z shibuya $

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

all:

clean:

install:
	mkdir -p $(PREFIX)/etc/init.d
	$(INSTALL) etc/init.d/mdchk $(PREFIX)/etc/init.d

