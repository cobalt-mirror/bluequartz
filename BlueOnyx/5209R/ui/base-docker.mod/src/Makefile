# Generic src makefile

ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif

include $(CCETOPDIR)/devel/defines.mk

DIRS=base-docker-am docker-yumconf base-docker-icon
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
		tar -zcvf /tmp/$$t.tar.gz $$t --exclude '.svn' &> /dev/null &&\
		$(RPMBUILD) -ta /tmp/$$t.tar.gz &> /tmp/rpm.log && \
		/bin/rm -f /xtmp/$$t.tar.gz ; \
	done

