# $Id: Makefile,v 1.4 2001/08/10 22:23:17 mpashniak Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../../dynamic.mk

INCLUDES += -I../include
INCLUDES += -I$(CCE_INCLUDEDIR) # for libdebug.h

COMPONENTS = ../conf.a ../../libcce_common.a

TESTS = test1

test: $(TESTS)
	./test1 testconf > test1.output 2>&1
	diff -u test1.output test.out.1

test1: test1.c $(COMPONENTS)
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS)

clean:
	rm -f $(TESTS)
	rm -f test_cscp_parse.output
	rm -f test1.output

