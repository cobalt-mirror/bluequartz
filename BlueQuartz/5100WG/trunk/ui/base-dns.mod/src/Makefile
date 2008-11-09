# Generic src makefile

DIRS=base-dns-am
TMPDIR=/tmp

all:
	for t in $(DIRS) ; do \
		make -C $$t all ; \
	done

install:
	for t in $(DIRS) ; do \
		make -C $$t install ; \
	done

rpm:
	for t in $(DIRS) ; do \
		tar -zcvf /tmp/$$t.tar.gz $$t --exclude '*CVS*' &> /dev/null &&\
		rpmbuild -ta /tmp/$$t.tar.gz &> /tmp/rpm.log && \
		/bin/rm -f /tmp/$$t.tar.gz ; \
	done

