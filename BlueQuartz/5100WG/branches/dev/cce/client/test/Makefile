# $Id: Makefile 229 2003-07-18 20:22:20Z will $ */
# Copyright (c) 2002 Sun Microsystems, Inc.  All rights reserved.
include ../../dynamic.mk

TESTS_BOTH = test_escapes test_converts test_props
TESTS_SHARED = $(TESTS_BOTH) test_nonexported
TESTS_STATIC = $(TESTS_BOTH:%=%.static)
TESTS = $(TESTS_SHARED) $(TESTS_STATIC)

test: $(TESTS)
	export LD_LIBRARY_PATH=..; \
	for test in $^; do echo ./$$test; ./$$test; done

$(TESTS_SHARED): %: %.c
	$(CC) -ggdb -Wall -Werror -o $@ $^ -L.. -lcce -I..

$(TESTS_STATIC): %.static: %.c
	$(CC) -ggdb -Wall -Werror -o $@ $^ -L.. -lcce -I.. -static \
		`glib-config --cflags` `glib-config --libs`

clean:
	$(RM) $(OBJS) $(TESTS)
	$(RM) .depend
