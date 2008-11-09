# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
include ../dynamic.mk

SRCS = cceclient.c
DIRS = pperl # ccewrap pam

INCLUDES += -I$(CCE_TOPDIR)/server
LIBS += -lcommon -L $(CCE_TOPDIR)/common

all: cceclient $(DIRS)
	$(warning WARNING: ccewrap and pam are not being compiled)

cceclient: %: %.c
	$(CC) $(CFLAGS) -o $@ $^ $(LIBS)

.PHONY: $(DIRS)
$(DIRS):
	$(MAKE) -C $@

test:
	for a in $(DIRS); do $(MAKE) -C $$a test || exit 1; done

clean:
	$(RM) cceclient
	for a in $(DIRS); do $(MAKE) -C $$a clean || exit 1; done

install:
	for a in $(DIRS); do $(MAKE) -C $$a install || exit 1; done
	mkdir -p $(CCE_BINDIR)
	$(INSTALL_BIN) cce_construct $(CCE_BINDIR)
	$(INSTALL_BIN) cceclient $(CCE_BINDIR)
