#
#	(C) Ge' Weijers 1997
#
#	$Id: Makefile,v 1.14.2.2 2002/04/06 01:18:35 pbaltz Exp $
#
# Copyright 2001 Sun Microsystems, Inc. All rights reserved.

VERSION:=1.0
RELEASE:=23
TARGET=sgalertd
CSOURCES=sgalertd.c readconfig.c
INCLUDE=
EXTRA=sgalertd.mspec rc.sgalertd send.c testoverflow am_sgalertd.sh sgtest sglist readconfig.h

PACKAGE=base-overflow
LOCALEDIR=/usr/share/locale

SUBDIR=sgalertd-$(VERSION)
SPECFILE=$(SUBDIR).spec
INSTALLDEST=$(PREFIX)/usr/sbin
TEMPLATE=po/template.tpo

DEPEND=.depend

ALLSOURCES=$(CSOURCES) $(INCLUDE) Makefile
TAR=$(SUBDIR).tar.gz
EMACSTEMP=$(ALLSOURCES:=~) $(EXTRA:=~) $(POFILES:=~)
OTHERJUNK=gmon.out core

OBJECTS=$(CSOURCES:.c=.o)

CPP=gcc
SCPP=/usr/sg/bin/gcc
CFLAGS=-g
CFLAGS:= -O3 -Wall 
CFLAGS+= -DCCE_ENABLED -I/usr/sausalito/include `glib-config --cflags`
#CFLAGS=-pg
XFLAGS:= -DPACKAGE="\"$(PACKAGE)\"" -DVERSION="\"$(VERSION)\"" 
XFLAGS+= -DLOCALEDIR="\"$(LOCALEDIR)\"" 
LFLAGS= -L/usr/sausalito/lib `glib-config --libs` -lcce -lpam

all: testoverflow $(TARGET) $(MOFILES)

$(TARGET): $(OBJECTS)
	$(CPP) $(LFLAGS) $(OBJECTS) -o $(TARGET)

install: $(TARGET) rc.sgalertd testoverflow
	install -d $(INSTALLDEST)
	install -m 0555 -s $(TARGET) $(INSTALLDEST)
	install -m 0555 -s testoverflow $(INSTALLDEST)
	install -d $(PREFIX)/etc/rc.d/init.d
	install -m 0555 rc.sgalertd $(PREFIX)/etc/rc.d/init.d/sgalertd
	install -d $(PREFIX)/usr/sausalito/swatch/bin
	install -m 0555 am_sgalertd.sh $(PREFIX)/usr/sausalito/swatch/bin/am_sgalertd.sh
	install -m 0555 sglist $(INSTALLDEST)
	install -m 0555 sgtest $(INSTALLDEST)

src: $(TAR)

rpm: $(TAR) $(SPECFILE) FOO
	cp $(TAR) /usr/src/redhat/SOURCES/
	cp $(SPECFILE) /usr/src/redhat/SPECS/
	rpm --clean -ba $(SPECFILE)

$(TAR): $(ALLSOURCES) $(EXTRA) $(SPECFILE) $(POFILES)
	-rm -f $(SUBDIR)
	ln -sf . $(SUBDIR)
	-rm -f $@
	tar cf - $(patsubst %,$(SUBDIR)/%,$^) | gzip -9 > $@
	-rm -f $(SUBDIR)

$(SPECFILE): sgalertd.mspec Makefile
	-rm -f $@
	sed -e "s/~VERSION~/$(VERSION)/" -e "s/~RELEASE~/$(RELEASE)/" < $< > $@

template: $(TEMPLATE)

$(TEMPLATE): $(CSOURCES)
	xgettext --foreign-user --keyword=_ $^ -o $@

ci:	$(ALLSOURCES) $(EXTRA) $(POFILES)
	ci -l $^

co:	$(ALLSOURCES) $(EXTRA) $(POFILES)
	co -l $^

clean:	FOO
	-rm -f $(OBJECTS) $(DEPEND) $(TARGET) $(EMACSTEMP) $(OTHERJUNK) $(TAR)
	-rm -f $(SPECFILE)
	-rm -rf locale $(TEMPLATE)
	-rm -f testoverflow testoverflow.o

FOO:

$(DEPEND):	$(CSOURCES) $(INCLUDE) Makefile
	-rm -f $(DEPEND)
	$(CPP) -MM $(CFLAGS) $(CSOURCES) > $@

%.o: %.c
	$(CPP) $(CFLAGS) $(XFLAGS) -c $<

$(OBJECTS): Makefile

.SUFFIXES:	.c .h .o

testoverflow:
	$(SCPP) -o testoverflow testoverflow.c

include $(DEPEND)
