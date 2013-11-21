# $Id: Makefile 1263 2009-09-16 06:04:39Z shibuya $

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
	$(INSTALL) etc/init.d/fix_grub $(PREFIX)/etc/init.d

