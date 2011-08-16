# $Id: Makefile,v 1.6 2001/08/10 22:23:18 mpashniak Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../../dynamic.mk

INCLUDES += -I$(CCE_INCLUDEDIR) # for libdebug.h

COMPONENTS = ../ed.a

TESTS = test1 test2 test3 test4

.PHONY: clean test

test: $(TESTS)
#		test1 and test2 currently don't run
#	./test1 >test1.output 2>&1
#	./test2 >test2.output 2>&1
# cleanup and create tmp for a codb location
# we need it clean so it gets the same oids
	rm -rf tmp
	mkdir -p tmp
	./test3 >test3.output 2>&1
	diff -u test3.output test3.out
	rm -rf tmp
	mkdir -p tmp
	./test4 >test4.output 2>&1
	diff -u test4.output test4.out

test1: test1.c $(COMPONENTS)
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS)

test2: test2.c $(COMPONENTS)
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS)

test3: test3.c $(COMPONENTS)
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS)

test4: test4.c $(COMPONENTS)
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS)

clean:
	rm -f $(TESTS)
	rm -f test1.output test2.output test3.output test4.output
	rm -rf tmp
