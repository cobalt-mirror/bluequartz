# $Id: Makefile 1188 2008-09-16 07:38:34Z mstauber $

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

