#
# This is a header to make a dunamic PHP extension for cce.
#
ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

PHP_BASE = /usr/local

CCELIBNAME = cce
CCESHLIBSRC = cce.c
CCEDEPSRC = $(CCESHLIBSRC)

INC = -I../include -I../../include -I../../../scalar/include `php-config --includes` -I$(CCEINCDIR)
CFLAGS = -g -Wall -Dbool=char -DHAS_BOOL -fPIC $(INC) `glib-config --cflags`
CCESHLIB_LIBS = -L../../src -lcce 


all: dep shlib

install: cce.so
	install -d $(CCEPHPLIBDIR)
	install $(INSTALL_SHLIBFLAGS) cce.so $(CCEPHPLIBDIR)
	install cce.ini $(PREFIX)/etc/php.d/

local_clean:
	find . -type f -name \*.o -exec /bin/rm -f \{} \; -print

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/rules.mk
else
include $(CCEDIR)/devel/rules.mk
endif
