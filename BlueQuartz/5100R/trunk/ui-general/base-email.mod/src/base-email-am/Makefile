# $Id: Makefile 1040 2007-12-16 04:43:14Z mstauber $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = am_pop.exp am_smtp.exp am_imap.exp am_pb4s.exp am_sasl.exp check-popb4smtp.sh check-sasl.sh

.PHONY: all
all: install

install: $(BINS)
	mkdir -p $(BINDIR)
	install -m 750 -o root -g root $(BINS) $(BINDIR)
