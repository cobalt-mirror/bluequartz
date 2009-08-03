# Makefile
# $Id: Makefile,v 1.1.1.1 2005/10/12 19:22:25 tagishandy Exp $

include ../dynamic.mk

CFLAGS=-Wall -fPIC
PAMDIR=$(PREFIX)/lib/security
CONFDIR=$(PREFIX)/etc/security
DBDIR=$(PREFIX)/var/lib/abl
LIBS=-ldb -lpthread
MODULE=pam_abl.so
OBJ=pam_abl.o log.o config.o rule.o
SUBDIRS=tools

all : $(MODULE)
	for d in $(SUBDIRS) ; do cd $$d && make $@ && cd .. ; done

$(MODULE) : $(OBJ)
	ld -x --shared $(LIBS) -o $@ $^

clean :
	rm -f $(MODULE) $(OBJ)
	for d in $(SUBDIRS) ; do cd $$d && make $@ && cd .. ; done

install : $(MODULE)
	install --mode=755 --strip $(MODULE) $(PAMDIR)
	install --mode=644 conf/pam_abl.conf $(CONFDIR)
	install -d --mode=755 $(DBDIR)
	for d in t $(SUBDIRS) ; do cd $$d && make $@ && cd .. ; done

depend :
	cc -MM *.c > deps
	for d in $(SUBDIRS) ; do cd $$d && make $@ && cd .. ; done

test :
	cd t && make && cd ..

include deps
