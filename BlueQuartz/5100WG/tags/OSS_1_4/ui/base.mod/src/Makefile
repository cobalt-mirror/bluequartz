TMPDIR=/tmp

ROOT=$(PREFIX)
CFLAGS += -Wall
LDFLAGS=-s
PROGS=usernetctl doexec netreport testd usleep

all:	$(PROGS)

clean:
	rm -f $(PROGS)

rpm:
	-rm -rf $(TMPDIR)/initutils
	-mkdir $(TMPDIR)/initutils
	tar --exclude '*CVS*' -cBf - . | (cd $(TMPDIR)/initutils; \
		tar xBf -)
	(cd $(TMPDIR); tar czf \
	/usr/src/redhat/SOURCES/initutils.tar.gz initutils)
	cp initutils.spec /usr/src/redhat/SPECS
	(cd /usr/src/redhat/SPECS; rpm -ba initutils.spec)
	-rm -rf $(TMPDIR)/initutils
	
install:
	mkdir -p $(ROOT)/bin $(ROOT)/usr/sbin $(ROOT)/usr/man/man1 $(ROOT)/sbin
	install -o 0 -g 0 -s -m 755 doexec $(ROOT)/bin/doexec
	install -o 0 -g 0 -s -m 755 usleep $(ROOT)/bin/usleep
	install -o 0 -g 0 -s -m 4755 usernetctl $(ROOT)/usr/sbin/usernetctl
	install -o 0 -g 0 -s -m 2755 netreport $(ROOT)/sbin/netreport
	install -m 644 doexec.1 $(ROOT)/usr/man/man1
	install -m 644 netreport.1 $(ROOT)/usr/man/man1
	install -m 644 usleep.1 $(ROOT)/usr/man/man1
	install -m 644 usernetctl.1 $(ROOT)/usr/man/man1

# this daemon and initscript are useful for testing the up/down/status stuff
# not installed by default, only comes from sources.
install-test:
	install -o 0 -g 0 -s -m 755 testd $(ROOT)/usr/sbin/testd
	install -o 0 -g 0 -m 755 testdinit $(ROOT)/etc/rc.d/init.d/testd

