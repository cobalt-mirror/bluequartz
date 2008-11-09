# $Id: Makefile,v 1.3 2001/10/15 20:30:21 jcheng Exp $

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
		rpm -ta /tmp/$$t.tar.gz &> /tmp/rpm.log && \
		/bin/rm -f /tmp/$$t.tar.gz ; \
		/bin/rm -rf /tmp/$$t ; \
	done

clean:
	true
