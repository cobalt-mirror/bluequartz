# $Id: Makefile,v 1.1 2001/09/25 04:56:21 jcheng Exp $

ifdef TOPDIR
include $(TOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

PRODUCT=$(shell if [ `uname -m` = "x86_64" ]; then if [ `cat /etc/build |grep 5209R|wc -l` = "1" ]; then echo -n "5209R"; else echo -n "5208R"; fi else echo -n "5207R"; fi)

DIRS=$(shell if [ "$(PRODUCT)" = "5209R" ]; then echo -n `ls -k --hide=Makefile | grep -v base-apache-bandwidth.mod`; else echo -n `ls -k --hide=Makefile`; fi)

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
