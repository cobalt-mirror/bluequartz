# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = am_cpu.sh am_mem.pl am_ecc.pl am_fans.pl am_temp.pl
PERLDIRS = ./AM
PERLSRCS = AM/Util.pm

all:
	true

install: $(BINS) $(PERLSRCS)
	mkdir -p $(BINDIR)
	install -m 750 -o root -g root $(BINS) $(BINDIR)
	install -d -o root -g root -m 0755 \
		$(patsubst ./%, $(CCEPERLDIR)/%, $(PERLDIRS))
	for t in $(PERLSRCS) ; do install -o root -g root -m 0755 \
		$$t $(CCEPERLDIR)/$$t ; done

