# $Id: Makefile 3 2003-07-17 15:19:15Z will $

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = raidState.pl

all:
	true

install: all
	mkdir -p $(BINDIR)
	install -m 755 -o root -g root $(BINS) $(BINDIR)
