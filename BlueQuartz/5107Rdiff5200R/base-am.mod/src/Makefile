# $Id: Makefile 1402 2010-03-10 14:12:15Z shibuya $

ifdef TOPDIR
include $(TOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

DIRS = am-bins

all:
	for t in $(DIRS) ; do \
		make -C $$t all ; \
	done

install: all
	for t in $(DIRS) ; do \
		make -C $$t install ; \
	done

rpm: 
	for t in $(DIRS) ; do \
		/bin/rm -rf /tmp/$$t ; \
		tar --exclude='.svn' -zcvf /tmp/$$t.tar.gz $$t &> /dev/null &&\
		$(RPMBUILD) -ta /tmp/$$t.tar.gz &> /tmp/rpm.log && \
		/bin/rm -f /tmp/$$t.tar.gz ; \
		/bin/rm -rf /tmp/$$t ; \
	done

clean:
	true
