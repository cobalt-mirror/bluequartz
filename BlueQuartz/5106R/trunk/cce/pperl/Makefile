# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Makefile for CCE persistent Perl

include ../dynamic.mk

BINS = pperl pperld

all: $(BINS)

pperl: pperl.c
	$(CC) $(CFLAGS) -o $@ $< $(LIBS)

pperld: pperld.pl
	cp -f $< $@

clean: 
	rm -f $(BINS) .depend

install: all
	mkdir -p $(CCE_BINDIR)
	$(INSTALL) $(INST_BINFLAGS) $(BINS) $(CCE_BINDIR)

uninstall:
	rm -f $(CCE_BINDIR)/pperld
	rm -f $(CCE_BINDIR)/pperl
	
depend: 
	# nothing
