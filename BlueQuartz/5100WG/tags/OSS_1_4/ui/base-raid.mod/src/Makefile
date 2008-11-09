# $Id: Makefile 3 2003-07-17 15:19:15Z will $

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

rpm: 
	tar --exclude CVS -C ../ -czvf /tmp/raid-bins.tar.gz src > /dev/null 2>&1
	rpm -ta /tmp/raid-bins.tar.gz > /tmp/rpm.log 2>&1
