#
# Makefile for creating telnet access utilities
#

.PHONY: telnet-scripts-rpm

DIRS=base-telnet-am

ROOT=$(PREFIX)
BINDIR = $(ROOT)/usr/sausalito/sbin
PERLDIR = $(ROOT)/usr/sausalito/perl

INSTALL = install
CHMOD   = chmod
CHOWN   = chown

PERLMOD = TelnetAccess.pm
SCRIPTS  = telnetAccess.pl initTelnet.sh

install: install-scripts install-am

all:
	@echo Nothing to do.

install-scripts: $(SCRIPTS)
	$(INSTALL) -d $(BINDIR)
	$(INSTALL) -m 500 $(SCRIPTS) $(BINDIR)
	$(INSTALL) -d $(PERLDIR)
	$(INSTALL) -m644 $(PERLMOD) $(PERLDIR)

install-am:
	for t in $(DIRS) ; do \
		make -C $$t install ; \
	done

rpm: rpm-scripts-telnet rpm-base-telnet-am

rpm-scripts-telnet:
	cd ..;tar -zcvf /usr/src/redhat/SOURCES/telnet-scripts.tar.gz src &> /dev/null;\
	cp src/telnet-scripts.spec /usr/src/redhat/SPECS;\
	touch /tmp/time.stamp;\
	rpm -ba /usr/src/redhat/SPECS/telnet-scripts.spec; \
	mkdir rpms; \
	cp `find /usr/src/redhat/RPMS -follow -type f -newer /tmp/time.stamp` rpms

rpm-base-telnet-am:
	for t in $(DIRS) ; do \
		tar -zcvf /tmp/$$t.tar.gz $$t --exclude '*CVS*' &> /dev/null &&\
		rpm -ta /tmp/$$t.tar.gz &> /tmp/rpm.log && \
		/bin/rm -f /tmp/$$t.tar.gz ; \
	done
