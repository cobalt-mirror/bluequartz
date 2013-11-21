# $Id: Makefile 1270 2009-09-16 07:19:25Z shibuya $

ifdef TOPDIR
include $(TOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

PERLSRCS = RAID.pm
PERLDIR = $(PREFIX)/usr/sausalito/perl

all:
	make -C am-bins all

install: all
	make PREFIX=$(PREFIX) -C am-bins install
	mkdir -p $(PERLDIR)/Cobalt
	for t in $(PERLSRCS) ; do install -o root -g root -m 755 $$t $(PERLDIR)/Cobalt ; done

	mkdir -p $(PREFIX)/usr/local/sbin
	$(INSTALL) $(INSTALL_SBINFLAGS) make_raid.sh $(PREFIX)/usr/local/sbin

rpm: 
	tar --exclude '.svn' --exclude CVS -C ../ -czvf /tmp/raid-bins.tar.gz src > /dev/null 2>&1
	rpmbuild -ta /tmp/raid-bins.tar.gz > /tmp/rpm.log 2>&1
