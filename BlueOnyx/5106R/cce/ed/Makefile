# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../dynamic.mk

MEMDEBUG = -DUSE_LIBDEBUG=1 -DUSE_MEMDEBUG=1
LIBS += -lcommon -ldebug -lcrypt 
DIRLIB = ed.a

SRCS = \
	src/handler_exec.c \
	src/handler_perl.c \
	src/handler_test.c \
	src/cce_ed.c
OBJS = $(patsubst %.c,%.o,$(SRCS))

all: $(DIRLIB)

$(DIRLIB): $(OBJS)
	$(AR) rcs $@ $^

debug:
	$(MAKE) all DEBUG="$(CCE_DEBUG) -DDEBUG_ED"

depend: .depend
.depend: $(SRCS)
	$(CC) -M $(CFLAGS) $(SRCS) $(TESTSRCS) > .depend

test:
	$(MAKE) -C test

clean:
	$(RM) -f $(DIRLIB) $(OBJS) .depend
	$(MAKE) -C test clean

# dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
