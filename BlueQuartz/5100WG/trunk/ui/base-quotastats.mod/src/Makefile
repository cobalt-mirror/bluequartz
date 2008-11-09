#
# Makefile for creating quotastats utilities
#

.PHONY: quotastats-scripts-rpm

ROOT=$(PREFIX)
BINDIR = $(ROOT)/usr/sausalito/sbin
PERLDIR = $(ROOT)/usr/sausalito/perl

INSTALL = install
CHMOD   = chmod
CHOWN   = chown

PERLMOD  =
SCRIPTS  = get_quota_stats.pl

install: install-all

all:
	@echo Nothing to do.

install-all: $(SCRIPTS)
	$(INSTALL) -d $(BINDIR)
	$(INSTALL) -d $(PERLDIR)
	$(INSTALL) -m 500 $(SCRIPTS) $(BINDIR)

rpm: rpm-scripts-quotastats

rpm-scripts-quotastats:
	cd ..;tar -zcvf quotastats-scripts.tar.gz src &> /dev/null;\
	cp quotastats-scripts.tar.gz /usr/src/redhat/SOURCES;\
	cp src/quotastats-scripts.spec /usr/src/redhat/SPECS;\
	rpmbuild -ba /usr/src/redhat/SPECS/quotastats-scripts.spec; \
	mkdir rpms; cp /usr/src/redhat/RPMS/i386/quotastats-scripts-1.01.1-1.i386.rpm rpms

