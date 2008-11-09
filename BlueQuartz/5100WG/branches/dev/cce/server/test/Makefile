# $Id: Makefile 229 2003-07-18 20:22:20Z will $

include ../../dynamic.mk

COMPONENTS = ../server.a

SCALAR_TESTS = scalar_test1 scalar_test2
TESTS = $(SCALAR_TESTS)

test: $(TESTS)
	./scalar_test1 >scalar_test1.out 2>&1
	./scalar_test2 >scalar_test2.out 2>&1

$(SCALAR_TESTS): %: %.c $(COMPONENTS)
	$(CC) $(CFLAGS) -o $@ $^ $(LIBS) -lcommon

clean:
	$(RM) $(TESTS) scalar*.out
