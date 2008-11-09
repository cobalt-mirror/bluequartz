# $Id: Makefile 266 2004-01-10 15:35:23Z shibuya $

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
		tar -zcvf /tmp/$$t.tar.gz $$t &> /dev/null &&\
		$(RPMBUILD) -ta /tmp/$$t.tar.gz &> /tmp/rpm.log && \
		/bin/rm -f /tmp/$$t.tar.gz ; \
		/bin/rm -rf /tmp/$$t ; \
	done

clean:
	true
