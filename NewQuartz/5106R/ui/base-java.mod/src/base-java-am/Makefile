# $Id: Makefile,v 1.1.2.1 2002/01/10 10:30:42 pbaltz Exp $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = am_java.exp

.PHONY: all
all: install

install: $(BINS)
	mkdir -p $(BINDIR)
	mkdir -p $(PREFIX)/etc/logrotate.d
	install -m 750 -o root -g root $(BINS) $(BINDIR)
