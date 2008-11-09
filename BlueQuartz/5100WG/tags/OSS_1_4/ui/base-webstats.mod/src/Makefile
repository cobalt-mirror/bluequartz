#
# Makefile for creating web stats utilities
#

.PHONY: webstats-scripts-rpm

ROOT=$(PREFIX)
BINDIR = $(ROOT)/usr/sausalito/sbin
PERLDIR = $(ROOT)/usr/sausalito/perl

INSTALL = install
CHMOD   = chmod
CHOWN   = chown

PERLMOD = WebLogParser.pm
SCRIPTS  = parseWebLog.pl reset_web_stats

install: install-all

all:
	@echo Nothing to do.

install-all: $(SCRIPTS)
	$(INSTALL) -d $(BINDIR)
	$(INSTALL) -m 500 $(SCRIPTS) $(BINDIR)
	$(INSTALL) -d $(PERLDIR)
	$(INSTALL) -m644 $(PERLMOD) $(PERLDIR)

rpm: rpm-scripts-webstats

rpm-scripts-webstats:
	cd ..;tar -zcvf webstats-scripts.tar.gz src &> /dev/null;\
	cp webstats-scripts.tar.gz /usr/src/redhat/SOURCES;\
	cp src/webstats-scripts.spec /usr/src/redhat/SPECS;\
	rpm -ba /usr/src/redhat/SPECS/webstats-scripts.spec; \
	mkdir rpms; cp /usr/src/redhat/RPMS/i386/webstats-scripts-1.01.1-1.i386.rpm rpms

