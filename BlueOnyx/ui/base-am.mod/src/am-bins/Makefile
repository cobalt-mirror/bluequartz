# $Id: Makefile 464 2005-04-13 06:09:07Z shibuya $

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

