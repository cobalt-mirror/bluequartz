# $Id: Makefile,v 1.2.2.1 2001/11/29 03:09:06 mpashniak Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../../dynamic.mk

INCLUDES += -I../include
INCLUDES += -I$(CCE_INCLUDEDIR) # for libdebug.h

COMPONENTS = ../cscp.a ../../libcce_common.a

TESTS = test_cscp_parse

test: $(TESTS)
	./test_cscp_parse < test_cscp_parse.in.1 >test_cscp_parse.output 2>&1
	diff -u test_cscp_parse.out.1 test_cscp_parse.output


test_cscp_parse: test_cscp_parse.c $(COMPONENTS)
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS)

clean:
	rm -f $(TESTS)
	rm -f test_cscp_parse.output

