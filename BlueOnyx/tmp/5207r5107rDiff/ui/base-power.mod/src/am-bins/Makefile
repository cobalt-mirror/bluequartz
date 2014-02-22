# $Id: Makefile,v 1.1 2001/10/19 04:07:13 jcheng Exp $

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = am_voltage.pl

all:
	true

install: $(BINS)
	mkdir -p $(BINDIR)
	install -m 750 -o root -g root $(BINS) $(BINDIR)
