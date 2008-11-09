# $Id: Makefile 3 2003-07-17 15:19:15Z will $

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
BINS = am_cpu.sh am_mem.pl
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

