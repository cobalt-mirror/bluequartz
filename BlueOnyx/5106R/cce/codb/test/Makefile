# $Id: Makefile,v 1.2 2001/08/10 22:23:11 mpashniak Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../../dynamic.mk

INCLUDES += -I../include -I$(CCE_INCLUDEDIR) # for libdebug.h

COMPONENTS = ../codb.a

TESTS = test_sorts

.PHONY: clean test

test: $(TESTS)
	./test_sorts >test_sorts.output 2>&1
	diff -u test_sorts.output test_sorts.out

test_sorts: test_sorts.c $(COMPONENTS)
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS)

clean:
	rm -f $(TESTS)
	rm -f test_sorts.output
