# Makefile for libscalar.a - used throughout sausalito

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CC = gcc
INCLUDES = \
	-I./include \
	-I../include \
	`glib-config --cflags` \
	-I$(CCEINCDIR)
DEFDEBUG = -ggdb -Wall $(DEBUG)
DEFS = -DLIBCCE_SCALAR_INTERNAL
CFLAGS = -O2 $(INCLUDES) $(DEFS) $(DEFDEBUG)
LIBS = -L$(CCELIBDIR) -ldebug -L.. -lcce_common `glib-config --libs`
SRCS = src/cce_scalar.c
OBJS = $(patsubst %.c,%.o,$(SRCS))
LIB = libcce_scalar.a
TESTS = test/test1 test/test2


all: .depend $(LIB)

$(LIB): $(OBJS) Makefile
	ar rcs $@ $(OBJS)
	ranlib $@

debug:
	make all DEBUG="-DDEBUG_LIBSCALAR"

.PHONY: test
test: testclean $(TESTS)

test/test1: test/test.o $(LIB)
	$(CC) -o $@ $^ $(LIBS)
	./test/test1

test/test2: test/test2.o $(LIB)
	$(CC) -o $@ $^ $(LIBS)
	./test/test2

install:
	# nothing to install

uninstall:
	# nothing to uninstall

setup:
	# noting to setup

depend: .depend

.depend: $(SRCS)
	$(CC) -E $(CFLAGS) -M $^ > .depend

clean: testclean
	rm -f *.a 
	find . -type f -name \*.o -exec /bin/rm -f \{} \; 
	rm -f core
	rm -f .depend

testclean:
	rm -f test/*.o
	rm -f test/test1 test/test2

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
