# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
include ../dynamic.mk

TITLE=pam_cce

DYNAMIC=-DPAM_DYNAMIC
INCLUDEDIR += -I../client/include -I../include/
CFLAGS = $(CCECFLAGS) `glib-config --cflags` $(INCLUDEDIR)
LD_D=gcc -shared -Xlinker -x -L../client/src/ -lcce
LIBSRC = $(TITLE).c
LIBOBJ = $(TITLE).o
LIBOBJD = $(addprefix dynamic/,$(LIBOBJ))
LIBOBJS = $(addprefix static/,$(LIBOBJ))
SECUREDIR = $(PREFIX)/lib/security

# for make test
USER=foo
PASS=bar

dynamic/%.o : %.c
	$(CC) $(CFLAGS) $(DYNAMIC) $(CPPFLAGS) $(TARGET_ARCH) -c $< -o $@

static/%.o : %.c
	$(CC) $(CFLAGS) $(STATIC) $(CPPFLAGS) $(TARGET_ARCH) -c $< -o $@

ifdef DYNAMIC
LIBSHARED = $(TITLE).so
endif

ifdef STATIC
LIBSTATIC = lib$(TITLE).a
endif

####################### don't edit below #######################

all: dirs $(LIBSHARED) $(LIBSTATIC) register

depend:
	# no dependencies here

dirs: dynamic static

dynamic:
ifdef DYNAMIC
	mkdir -p ./dynamic
endif

static:
ifdef STATIC
	mkdir -p ./static
endif

register:
ifdef STATIC
	( cd .. ; ./register_static $(TITLE) $(TITLE)/$(LIBSTATIC) )
endif

ifdef DYNAMIC
$(LIBOBJD): $(LIBSRC)

$(LIBSHARED):	$(LIBOBJD)
	$(LD_D) -o $@ $(LIBOBJD)
endif

ifdef STATIC
$(LIBOBJS): $(LIBSRC)

$(LIBSTATIC): $(LIBOBJS)
	$(LD) -r -o $@ $(LIBOBJS)
	ar rcs $@ $^
endif

install: all
	mkdir -p $(SECUREDIR) 
ifdef DYNAMIC
	$(INSTALL) $(INSTALL_SHLIBFLAGS) $(LIBSHARED) $(FAKEROOT)$(SECUREDIR)
endif

test: all test_pam_cce
	./test_pam_cce $(USER) $(PASS)

test_pam_cce:
	$(CC) -ggdb -o test_pam_cce test_pam_cce.c -lpam -ldl

remove:
	rm -f $(FAKEROOT)$(SECUREDIR)/$(TITLE).so

clean:
	rm -f $(LIBOBJD) $(LIBOBJS) $(LIBSTATIC) $(LIBSHARED) core *~
	rm -rf dynamic static
	rm -f test_pam_cce

extraclean: clean
	rm -f *.a *.o *.so *.bak dynamic/* static/*

.c.o:	
	$(CC) $(CFLAGS) -c $<

