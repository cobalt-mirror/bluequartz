# Makefile for cce_conf
#

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CC = gcc
DEFDEBUG = -ggdb -Wall $(DEBUG) #-pedantic
DEFS =
LIBS = \
	`glib-config --libs` \
	-L$(CCELIBDIR) -ldebug 
INCLUDES = \
	-I. \
	-I./include \
	-I../include \
	-I$(CCEINCDIR)
CFLAGS = -O2 $(DEFS) $(INCLUDES) `glib-config --cflags` $(DEFDEBUG)

LIB = libcce_conf.a

TESTS = test/test1

SRCS = \
	src/conf.c \
	src/conf_handler.c
OBJS = $(patsubst %.c,%.o,$(SRCS))

all: depend $(LIB)

$(LIB): $(OBJS)
	ar rcs $@ $^
	ranlib $@

debug:
	make all DEBUG="-DDEBUG_CONF"

doc: include/cce_conf.h
	/bin/rm -rf html
	doc++ -H -a -f -M -j -d html $^

test: testclean $(TESTS)

test/test1: src/test/test.c $(LIB)
	$(CC) $(CFLAGS) -DDEBUG_CONF -o $@ $^ $(LIBS)
	$@ test/testconf > test/test.out.new 2>/dev/null
	@diff -u test/test.out.new test/test.out.1 || (echo TEST FAILED && false)
	@echo TEST PASSED

install:
	# nothing to install

uninstall:
	# nothing to uninstall

setup:
	# FIXME: do this

.depend: $(SRCS)
	$(CC) -E $(CFLAGS) -M $^ > .depend
	
depend: .depend

testclean:
	rm -f $(TESTS)

clean: testclean
	rm -f $(LIB) $(OBJS)
	rm -f `find . -type f -name \*.o -print`
	rm -f src/conf_parser.c
	rm -f $(TEST) test/test.out.new
	rm -f .depend

#dependencies
ifeq (.depend,$(wildcard .depend))
include .depend
endif
