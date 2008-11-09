# $Id: Makefile 3 2003-07-17 15:19:15Z will $

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CC = gcc
INCLUDES = -I./include -I$(CCEINCDIR) -I../include
DEFS = 
DEFDEBUG = -ggdb -Wall $(DEBUG)
MEMDEBUG = -DUSE_LIBDEBUG=1 -DUSE_MEMDEBUG=1
CFLAGS = -O2 $(INCLUDES) $(DEFS) `glib-config --cflags` $(DEFDEBUG)
LDCONFIG = /sbin/ldconfig

LIB = libcce_session.a

SRCS = src/sessionmgr.c 

OBJS = $(patsubst %.c,%.o,$(SRCS))

all: .depend $(LIB)

debug:
	make all DEBUG="-DDEBUG_SESSION"

$(LIB): $(OBJS)
	ar rcs $@ $^
	ranlib $@

clean:
	/bin/rm -f `find . -type f -name \*.o -print`
	/bin/rm -f `find . -type f -name core`
	/bin/rm -f .depend
	/bin/rm -f $(LIB)*

depend: .depend

install:
	# nothing to install

.depend:
	gcc -M $(CFLAGS) $(SRCS) $(TESTSRCS) > .depend

# dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
