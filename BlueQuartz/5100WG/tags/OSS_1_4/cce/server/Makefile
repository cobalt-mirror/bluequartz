# Makefile for CCE Daemon
# $Id: Makefile 3 2003-07-17 15:19:15Z will $

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

include ../version.mk

CCEDDIR := $(shell pwd)

CC = gcc
DEFDEBUG = -ggdb -Wall $(DEBUG) #-pedantic
DEFS = -DCCE_VERSION=\"$(CCE_VERSION)\"
LIBDIRS = -L.. -L$(CCELIBDIR) -L/usr/sausalito/lib
LIBS = \
	$(LIBDIRS) \
	`glib-config --libs` \
	-lcce_common \
	-lcrypt \
	-lpam \
	-ldl \
	-ldebug 
INCLUDES = \
	-I. \
	-I$(CCEDDIR)/include \
	-I$(CCEDDIR)/../include \
	-I$(CCEINCDIR) \
	-I/usr/sausalito/include
CFLAGS = -O2 $(DEFS) $(INCLUDES) `glib-config --cflags` $(DEFDEBUG)

SRCS = src/main.c src/smd.c
OBJS = $(patsubst %.c,%.o,$(SRCS))

CCED = cced

all: $(CCED) cceclient

	
$(CCED): $(OBJS) ../libcce_common.a
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS) 

cceclient: src/testcli.c ../libcce_common.a
	$(CC) $(CFLAGS) -o $@ $< $(LIBS)

debug:
	make all DEBUG="-DDEBUG_CCED"

test: all
	./cced >/dev/null 2>&1 &
	./cceclient /tmp/cce < test/test_input > tmp.out
	egrep "^[12349]" test/test_output | grep -v ^104 > good.out
	egrep "^[12349]" tmp.out | grep -v ^104 > test.out
	killall cced
	@diff -u good.out test.out >/dev/null 2>&1 \
		|| (echo TEST FAILED && false)
	@echo TEST PASSED
	
install: all
	-mkdir -p $(CCESBINDIR)
	-mkdir -p $(CCEBINDIR)
	-mkdir -p $(CCESCHEMASDIR)
	-mkdir -p $(PREFIX)/etc/rc.d/init.d
	$(INSTALL) $(INSTALL_SBINFLAGS) $(CCED) $(CCESBINDIR)
	@echo Put $(CCED) in $(CCESBINDIR)
	$(INSTALL) $(INSTALL_BINFLAGS) cceclient $(CCEBINDIR)
	@echo Put cceclient in $(CCEBINDIR)
	$(INSTALL) $(INSTALL_BINFLAGS) cce_construct $(CCEBINDIR)
	@echo Put cce_construct in $(CCEBINDIR)
	rm -f $(CCESCHEMASDIR)/cced.schema
	$(INSTALL) $(INSTALL_HEADERFLAGS) basetypes.schema $(CCESCHEMASDIR)
	@echo Put basetypes.schema in $(CCESCHEMASDIR)
	$(INSTALL) $(INSTALL_BINFLAGS) cced.init $(PREFIX)/etc/rc.d/init.d/
	@echo Put cced.init in $(PREFIX)/etc/rc.d/init.d
	$(INSTALL) $(INSTALL_HEADERFLAGS) cced.pam $(PREFIX)/etc/pam.d/cced
	@echo Put cced.pam in $(PREFIX)/etc/pam.d

uninstall:
	rm -f $(CCESBINDIR)/$(CCED)
	rm -f $(CCEBINDIR)/cceclient
	rm -f $(CCESCHEMASDIR)/cced.schema
	rm -f $(CCESCHEMASDIR)/basetypes.schema
	rm -f $(PREFIX)/etc/rc.d/init.d/cced
	rm -f $(PREFIX)/etc/pam.d/cced
	# nothing to uninstall (yet)

setup:
	# nothing to setup (yet)
	
depend: $(SRCS)
	$(CC) -E $(CFLAGS) -M $^ > .depend

clean:
	rm -f $(OBJS)
	rm -f $(CCED)
	rm -f *.out
	rm -f cceclient
	rm -f .depend core

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/package.mk
else
include $(CCEDIR)/devel/package.mk
endif
