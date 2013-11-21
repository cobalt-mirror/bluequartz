# $Id: Makefile 1382 2010-02-21 12:11:21Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
include ../../dynamic.mk

LIBNAME = libcce
STATIC_LIB = $(LIBNAME).a
SHARED_LIB = $(LIBNAME).so
SHARED_LIB_MAJ = $(SHARED_LIB).$(CCE_V_MAJ)
SHARED_LIB_FULL = $(SHARED_LIB_MAJ).$(CCE_V_MIN)

CFLAGS += -D_REENTRANT -D_GNU_SOURCE
DEFS += -DVERSION="\"$(CCE_VERSION)\""
INCLUDES += -I../include

STATIC_CFLAGS = $(CFLAGS)
SHARED_CFLAGS = $(CFLAGS) -fPIC
SHARED_LDFLAGS = $(LDFLAGS) -shared -rdynamic -Wl,-rpath,$(CCE_LIBDIR)

SRCS = c6.c cce.c ccelib.c exported.c safe.c
STATIC_OBJS = $(patsubst %.c,%.o,$(SRCS))
SHARED_OBJS = $(patsubst %.c,%.do,$(SRCS))
OBJS = $(STATIC_OBJS) $(SHARED_OBJS)

# build rules 
.c.o:
	$(CC) $(STATIC_CFLAGS) -c -o $@ $<

.c.do:
	$(CC) $(SHARED_CFLAGS) -c -o $@ $<

.SUFFIXES: .do
	
# the work rules
all: shared-lib #static-lib

# we don't really need a static library
#static-lib: $(STATIC_LIB)
#$(STATIC_LIB): $(STATIC_OBJS)
#	$(AR) rcs $@ $^
	
shared-lib: $(SHARED_LIB_FULL)
$(SHARED_LIB_FULL): $(SHARED_OBJS)
	rm -f $(SHARED_LIB_MAJ) $(SHARED_LIB)
	$(CC) -Wl,-soname,$(SHARED_LIB_MAJ) $^ -o $@ $(SHARED_LDFLAGS) $(LIBS)
	ln -sf $@ $(SHARED_LIB_MAJ)
	ln -sf $@ $(SHARED_LIB)

install: install-libs install-headers 

install-libs: $(SHARED_LIB_FULL)
	mkdir -p $(CCE_LIBDIR)
	$(INSTALL) $(INST_BINFLAGS) $(SHARED_LIB_FULL) $(CCE_LIBDIR)
	ln -sf $(SHARED_LIB_FULL) $(CCE_LIBDIR)/$(SHARED_LIB_MAJ)
	ln -sf $(SHARED_LIB_FULL) $(CCE_LIBDIR)/$(SHARED_LIB)
	if [ `id -u` = 0 ]; then $(LDCONFIG); fi
	
install-headers:
	mkdir -p $(CCE_INCLUDEDIR)/cce
	install -o root -g root -m 644 ../include/c6.h $(CCE_INCLUDEDIR)/cce
	install -o root -g root -m 644 ../include/cce.h $(CCE_INCLUDEDIR)/cce

clean:
	$(RM) -f $(OBJS) $(STATIC_LIB) $(SHARED_LIB)* .depend

debug:
	$(MAKE) all DEBUG="$(CCE_DEBUG)"

depend: .depend
.depend: $(LIBSRCS)
	$(CC) -E $(CFLAGS) -M $@ > $@

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
