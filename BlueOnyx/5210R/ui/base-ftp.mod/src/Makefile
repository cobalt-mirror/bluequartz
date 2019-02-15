# Generic src makefile

ifdef TOPDIR
include $(TOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

DIRS=base-ftp-am
RPMDIRS=base-ftp-am net2ftp
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
	for t in $(RPMDIRS) ; do \
		tar zcvf /tmp/$$t.tar.gz --exclude '.svn' $$t &> /dev/null &&\
		$(RPMBUILD) -ta /tmp/$$t.tar.gz &> /tmp/rpm.log && \
		/bin/rm -f /tmp/$$t.tar.gz ; \
	done

