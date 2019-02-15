# $Id: Makefile,v 1.1 2001/09/25 04:56:21 jcheng Exp $

ifdef TOPDIR
include $(TOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

PRODUCT=$(shell echo -n "5210R")

DIRS=$(shell if [ "$(PRODUCT)" = "5210R" ]; then echo -n `ls -k --hide=Makefile | grep -v base-apache-bandwidth.mod`; else echo -n `ls -k --hide=Makefile`; fi)

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
