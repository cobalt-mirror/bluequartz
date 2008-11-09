# $Id: Makefile 3 2003-07-17 15:19:15Z will $

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CC = gcc
INCLUDES = -I./include \
	-I../include \
	-I$(CCEINCDIR)
DEFS = 
DEFDEBUG = -ggdb -Wall $(DEBUG)
MEMDEBUG = -DUSE_LIBDEBUG=1 -DUSE_MEMDEBUG=1
CFLAGS = -O2 $(INCLUDES) $(DEFS) `glib-config --cflags` $(DEFDEBUG)
LIBDIRS = -L$(CCELIBDIR) -L..
LIBS = \
	$(LIBDIRS) \
	`glib-config --libs` \
	-lcommon \
	-ldebug \
	-lcrypt
LDCONFIG = /sbin/ldconfig

LIB = libcce_ed.a

SRCS = \
	src/handler_exec.c \
	src/handler_perl.c \
	src/handler_test.c \
	src/cce_ed.c

OBJS = $(patsubst %.c,%.o,$(SRCS))

TESTSRCS = \
	test/test1.c \
	test/test2.c

TESTOBJS = $(patsubst %.c,%.o,$(TESTSRCS))

all: .depend $(LIB)

debug:
	make all DEBUG="-DDEBUG_ED"

$(LIB): $(OBJS)
	ar rcs $@ $^
	ranlib $@

.PHONY: test/test1 test/test2 test
test/test1: test/test1.o $(LIB) $(OTHERLIBS)
	gcc -o test/test1 $^ $(LIBS) $(MEMDEBUG)
	cd test ; ./test1 
	cd ..

test/test2: test/test2.o $(LIB) $(OTHERLIBS)
	gcc -o test/test2 $^ $(LIBS) $(MEMDEBUG)
	cd test; ./test2
	cd ..

test/test3: test/test3.o  $(LIB) $(OTHERLIBS)
	gcc -o test/test3 $^ $(LIBS) $(MEMDEBUG)
	cd test ; ./test3
	cd ..

test: test/test1

clean:
	/bin/rm -f `find . -type f -name \*.o -print`
	/bin/rm -f `find . -type f -name core`
	/bin/rm -rf `find . -type d -name codb -print`
	/bin/rm -rf `find . -type f -name codb.oids -print`
	/bin/rm -f .depend
	/bin/rm -f $(LIB)*

depend: .depend

install:
	# nothing to install

.depend: $(SRCS)
	gcc -M $(CFLAGS) $(SRCS) $(TESTSRCS) > .depend

# dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
