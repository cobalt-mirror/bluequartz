# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

include ../../dynamic.mk

TESTS = argparsetest booltest hashwraptest propstest stresctest

test:	$(TESTS)
	./argparsetest
	@while read B; do \
		echo $$B | ./booltest; \
	done < bool.test.in > bool.test.out
	diff -u bool.test.out bool.test.expected.out >/dev/null 2>&1
	./hashwraptest
	./propstest
	./stresctest

$(TESTS): %: %.c ../libcommon.a
	$(CC) -o $@ $(CFLAGS) $^ $(LIBS) -lcommon

clean:
	$(RM) $(TESTS) bool.test.out
