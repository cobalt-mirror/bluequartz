# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../dynamic.mk

MEMDEBUG = -DUSE_LIBDEBUG=1 -DUSE_MEMDEBUG=1

SRCS = src/sessionmgr.c 
OBJS = $(patsubst %.c,%.o,$(SRCS))
DIRLIB = sessionmgr.a

all: $(DIRLIB)

$(DIRLIB): $(OBJS)
	$(AR) rcs $@ $^

debug:
	$(MAKE) all DEBUG="$(CCE_DEBUG) -DDEBUG_SESSION"

test:
	# nothing

depend: .depend
.depend:
	$(CC) -M $(CFLAGS) $(SRCS) $(TESTSRCS) > .depend

clean:
	$(RM) -f $(DIRLIB) .depend $(OBJS)

# dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
