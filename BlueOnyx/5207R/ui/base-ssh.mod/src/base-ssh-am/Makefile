# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = am_sshd.pl

.PHONY: all
all: install

install: $(BINS)
	mkdir -p $(BINDIR)
	install -m 750 -o root -g root $(BINS) $(BINDIR)
