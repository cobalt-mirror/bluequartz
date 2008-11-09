# $Id: Makefile 201 2003-07-18 19:11:07Z will $

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = raidState.pl raid_amdetails.pl
SECUREBINS = smart-status.pl dma_test.pl
SBINS = ide-smart
SBINDIR = $(PREFIX)/usr/local/sbin

all: ide-smart
	true

install: $(SBINS) $(BINS)
	mkdir -p $(BINDIR)
	install -m 750 -o root -g root $(SECUREBINS) $(BINDIR)
	install -m 755 -o root -g root $(BINS) $(BINDIR)
	mkdir -p $(SBINDIR)
	install -m 755 -o root -g root $(SBINS) $(SBINDIR)

ide-smart: ide-smart.proc.c
	gcc -o ide-smart ide-smart.proc.c
