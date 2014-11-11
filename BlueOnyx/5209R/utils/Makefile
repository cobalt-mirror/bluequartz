# $Id: Makefile,v 1.1 2001/09/25 04:56:21 jcheng Exp $

ifdef TOPDIR
include $(TOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

DIRS = cce swatch

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
		make -C $$t rpm; \
	done

clean:
	for t in $(DIRS) ; do \
		make -C $$t clean; \
	done
