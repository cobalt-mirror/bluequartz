# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../../dynamic.mk

COMPONENTS = ../common.a

TESTS = argparsetest booltest

.PHONY: clean test

test:	$(TESTS)
	./argparsetest
	@while read B; do echo $$B | ./booltest; done < bool.test.in > bool.test.out;
	diff -u bool.test.out bool.test.expected.out >/dev/null 2>&1;


argparsetest: argparsetest.c $(COMPONENTS)
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS)

booltest: booltest.c $(COMPONENTS)
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS) -lfl

clean:
	rm -f core $(TESTS) bool.test.out
