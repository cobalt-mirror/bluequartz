# $Id: Makefile 685 2006-01-16 08:36:21Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# This is a header to make a dunamic PHP extension for cce.
#
include ../../../dynamic.mk

LIBARCH=$(shell if [ `uname -m` = "x86_64" ]; then  echo "lib64"; else  echo "lib"; fi)

PHP_BASE = `php-config --prefix`

LIBNAME = cce
SHARED_LIB = $(LIBNAME).so
CCEPHPLIBDIR=$(PREFIX)$(shell php-config --extension-dir)
CCEPHPCLASSDIR=$(CCE_INSTALLDIR)/ui/libPhp

#CFLAGS += -Dbool=char -DHAS_BOOL -fPIC -D_REENTRANT 
CFLAGS = -ggdb -Werror  -D_GNU_SOURCE -I/usr/include/glib-2.0/ -I/usr/$(LIBARCH)/glib-2.0/include -I/root/cce/include -I../include -I../../include -I../../../include `php-config --includes` -fPIC -Dbool=char -DHAS_BOOL -fPIC -D_REENTRANT
#CFLAGS = -ggdb -D_GNU_SOURCE -I/usr/include/glib-2.0/ -I/usr/$(LIBARCH)/glib-2.0/include -I/root/cce/include -I../include -I../../include -I../../../include `php-config --includes` -fPIC -Dbool=char -DHAS_BOOL -fPIC -D_REENTRANT
SHCFLAGS = -shared -rdynamic  -Wl,-rpath,$(CCE_LIBDIR)
INCLUDES += -I../include -I../../include \
	`php-config --includes`
LIBS += -L../../src -lcce 

SRCS = cce.c
OBJS = $(SRCS:.c=.do)

.c.do:
	$(CC) $(CFLAGS) -c -o $@ $<

.SUFFIXES: .do

all: $(SHARED_LIB)

$(SHARED_LIB): $(OBJS)
	$(CC) $(SHCFLAGS) $^ -o cce.so $(LIBS)

install: $(SHARED_LIB)
	$(INSTALL) -d $(CCEPHPLIBDIR)
	$(INSTALL) $(INST_BINFLAGS) cce.so $(CCEPHPLIBDIR)

clean:
	$(RM) -f $(OBJS) $(SHARED_LIB) .depend
