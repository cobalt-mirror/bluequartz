# Makefile for swatch
# $Id$

include /usr/sausalito/devel/defines.mk

.EXPORT_ALL_VARIABLES:

TOPDIR := $(shell pwd)

V_MAJ=4
V_MIN=0
V_PATCH=0
SWATCH_VERSION=$(V_MAJ).$(V_MIN).$(V_PATCH)

CC = gcc
DEFS = -DSWATCH_VERSION=\"$(SWATCH_VERSION)\"
DEFDEBUG = -ggdb -Wall $(DEBUG)
DBG_LIBS = -ldebug
INCLUDES = -I. -I$(TOPDIR)/include -I/usr/sausalito/include
CFLAGS = -O2 `glib-config --cflags` $(DEFS) $(INCLUDES) $(DEFDEBUG)
LIBS = `glib-config --libs` -L/usr/sausalito/lib -lcce -li18n $(DBG_LIBS)
INSTFLAGS = -o root -g root

SBINDIR=$(PREFIX)/usr/sbin
CONFDIR=$(PREFIX)/etc
INITDIR=$(PREFIX)/etc/rc.d/init.d
CRONDIR=$(PREFIX)/etc/cron.d
SWATCHDIR=$(PREFIX)/usr/sausalito/swatch
SAUSBINDIR=$(PREFIX)/usr/sausalito/sbin


all: 
	make -C locale

debug:
	make all DEBUG="-DDEBUG_SWATCH" DBG_LIBS="-ldebug"

test:
	# no tests

install: all
	install $(INSTFLAGS) -m 0750 swatch-cobalt.pl $(SBINDIR)/swatch
	install $(INSTFLAGS) -m 0644 conf/swatch.conf $(CONFDIR)/swatch.conf
	install $(INSTFLAGS) -m 0644 conf/statecodes $(SWATCHDIR)
	install $(INSTFLAGS) -m 0644 conf/statecodes.tcl $(SWATCHDIR)
	install $(INSTFLAGS) -m 0644 swatch.cron $(CRONDIR)
	install $(INSTFLAGS) -m 0750 swatch.sh $(SAUSBINDIR)/swatch.sh
	install $(INSTFLAGS) -m 0750 hotfixes.sh $(SAUSBINDIR)/hotfixes.sh

	make -C locale install

uninstall:
	rm -f $(SBINDIR)/swatch
	rm -f $(CONFDIR)/swatch.conf
	rm -f $(CRONDIR)/swatch.cron
	rm -f $(SWATCHDIR)/statecodes
	rm -f $(SWATCHDIR)/statecodes.tcl
	rm -f $(SAUSBINDIR)/swatch.sh
	rm -f $(SAUSBINDIR)/hotfixes.sh
	make -C locale uninstall

rpm: clean
	touch .timestamp
	cat swatch.spec.in | sed 's/XX_VER_XX/$(SWATCH_VERSION)/' > swatch.spec
	CUR=`pwd`; \
	DIR=`basename $$CUR`; \
	cd ..; \
	tar --exclude=CVS -zcvf /tmp/swatch.tar.gz $$DIR; \
	unset TOPDIR; \
	rpmbuild -ta /tmp/swatch.tar.gz
	mkdir -p rpms
	cp `find $(PACKAGE_DIR)/RPMS -follow -type f -newer .timestamp` rpms 
	mkdir -p srpms
	cp `find $(PACKAGE_DIR)/SRPMS -follow -type f -newer .timestamp` srpms 
	rm .timestamp

clean:
	make -C locale clean
	rm -f .depend core swatch.spec
	rm -rf rpms srpms
