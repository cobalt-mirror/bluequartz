# $Id: Makefile,v 1.2 2001/08/10 22:23:19 mpashniak Exp $
# $Id: Makefile,v 1.2 2001/08/10 22:23:19 mpashniak Exp $

include ../../dynamic.mk

INCLUDES += -I$(CCE_INCLUDEDIR) # for libdebug.h

COMPONENTS = ../scalar.a ../../libcce_common.a

TESTS = test1 test2

test: $(TESTS)
	./test1 >test1.output 2>&1
	./test2 >test2.output 2>&1

test1: test1.c $(COMPONENTS)
	$(CC) $(CFLAGS) -o $@ $^ $(LIBS)

test2: test2.c $(COMPONENTS)
	$(CC) $(CFLAGS) -o $@ $^ $(LIBS)

clean:
	$(RM) -f $(TESTS) test1.output test2.output


