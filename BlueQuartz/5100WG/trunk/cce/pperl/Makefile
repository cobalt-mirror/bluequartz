# Makefile for CCE persistent Perl
# $Id: Makefile 3 2003-07-17 15:19:15Z will $

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

BINS = pperl pperld

DEBUG = -Wall -ggdb
INCLUDES = -I../include -L../
CFLAGS = -O2 $(INCLUDES) $(DEBUG)
LDFLAGS = -lcce_common

all: $(BINS)

pperl: pperl.c
	$(CC) $(CFLAGS) -o $@ $< $(LDFLAGS)

pperld: pperld.pl
	cp -f $< $@

clean: 
	rm -f $(BINS)
	rm -f .depend core *.o

install: all
	mkdir -p $(CCEBINDIR)
	$(INSTALL) -m 755 -o root -g root $(BINS) $(CCEBINDIR)

uninstall:
	rm -f $(CCEBINDIR)/pperld
	rm -f $(CCEBINDIR)/pperl
	
depend: 
	true

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/package.mk
else
include $(CCEDIR)/devel/package.mk
endif
