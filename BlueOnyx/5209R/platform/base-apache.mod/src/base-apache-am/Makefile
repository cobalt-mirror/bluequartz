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
BINS = am_apache.exp am_apache.sh am_admserv.exp am_phpfpm.pl am_phpfpm53.pl am_phpfpm54.pl am_phpfpm55.pl am_phpfpm56.pl am_apache.pl

.PHONY: all
all: install

install: $(BINS)
	mkdir -p $(BINDIR)
	install -m 750 -o root -g root $(BINS) $(BINDIR)
