# $Id: Makefile 201 2003-07-18 19:11:07Z will $

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = am_disk.pl
SBINS = get_quotas.pl
SBINDIR = $(CCESBINDIR)

all:
	true

install: all
	mkdir -p $(BINDIR)
	install -m 750 -o root -g root $(BINS) $(BINDIR)
	mkdir -p $(SBINDIR)
	install -m 750 -o root -g root $(SBINS) $(SBINDIR)
