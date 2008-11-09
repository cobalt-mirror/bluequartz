# $Id: Makefile 229 2003-07-18 20:22:20Z will $ */
# Copyright (c) 2002 Sun Microsystems, Inc.  All rights reserved.
include ../dynamic.mk

LIBNAME = libcce
STATIC_LIB = $(LIBNAME).a
SHARED_LIB_NOVER = $(LIBNAME).so
SHARED_LIB_MAJOR = $(SHARED_LIB_NOVER).$(CCE_V_MAJOR)
SHARED_LIB = $(SHARED_LIB_MAJOR).$(CCE_V_MINOR)

CFLAGS += -D_REENTRANT $(DEBUG)
#FIXME: needs to know CSCP version
DEFS += -DCCE_VERSION=\"$(CCE_VERSION)\"
DEBUG = -ggdb -O0 -DCCE_DEBUG_LIB

STATIC_CFLAGS = $(CFLAGS)
SHARED_CFLAGS = $(CFLAGS) -fPIC
SHARED_LDFLAGS = $(LDFLAGS) -shared -rdynamic -Xlinker -Bsymbolic

DIST_HEADERS = cce.h

SRCS = ccelib.c cce_props.c transforms.c
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
all: .depend $(STATIC_LIB) $(SHARED_LIB)

$(STATIC_LIB): $(STATIC_OBJS) ../common/common.a
	$(LD) -r -o $(LIBNAME).o $^
	$(AR) rcs $@ $(LIBNAME).o

$(SHARED_LIB): $(SHARED_OBJS) ../common/common.a
	$(RM) $(SHARED_LIB_MAJOR) $(SHARED_LIB_NOVER)
	$(CC) -Wl,-soname,$(SHARED_LIB_MAJOR) $^ -o $@ $(SHARED_LDFLAGS) $(LIBS)
	ln -sf $@ $(SHARED_LIB_MAJOR)
	ln -sf $@ $(SHARED_LIB_NOVER)

install: install-libs install-headers

install-libs: $(SHARED_LIB) $(STATIC_LIB)
	mkdir -p $(CCE_LIBDIR)
	$(INSTALL_BIN) $(SHARED_LIB) $(CCE_LIBDIR)
	ln -sf $(SHARED_LIB) $(CCE_LIBDIR)/$(SHARED_LIB_MAJOR)
	ln -sf $(SHARED_LIB) $(CCE_LIBDIR)/$(SHARED_LIB_NOVER)
	/sbin/ldconfig

install-headers: $(DIST_HEADERS)
	mkdir -p $(CCE_INCLUDEDIR)
	$(INSTALL_FILE) $(DIST_HEADERS) $(CCE_INCLUDEDIR)

test:
	$(MAKE) -C test test

clean:
	$(RM) $(OBJS) $(STATIC_LIB) $(SHARED_LIB_NOVER)* $(TESTS)
	$(MAKE) -C test clean
	$(RM) .depend

.depend depend dep: $(SRCS)
	$(CC) $(CFLAGS) -MM $^ > .depend
	$(CC) $(CFLAGS) -MM $^ | sed 's/^\(.*\).o: /\1.do: /' >> .depend

ifeq (.depend,$(wildcard .depend))
include .depend
endif
