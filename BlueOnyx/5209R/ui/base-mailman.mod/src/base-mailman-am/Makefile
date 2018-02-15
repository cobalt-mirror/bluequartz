# $Id: Makefile
#
# Copyright 2011 Team BlueOnyx. All rights reserved.
#

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = am_mailman.exp check-mailman.sh

.PHONY: all
all: install

install: $(BINS)
	mkdir -p $(BINDIR)
	install -m 750 -o root -g root $(BINS) $(BINDIR)
