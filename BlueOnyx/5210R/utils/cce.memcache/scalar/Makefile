# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Makefile for libscalar.a - used throughout sausalito

include ../dynamic.mk

SRCS = src/cce_scalar.c
OBJS = $(patsubst %.c,%.o,$(SRCS))
DIRLIB = scalar.a

all: $(DIRLIB)

$(DIRLIB): $(OBJS)
	ar rcs $(DIRLIB) $(OBJS)

debug:
	$(MAKE) all DEBUG="$(CCE_DEBUG) -DDEBUG_LIBSCALAR"

depend: .depend
.depend: $(SRCS)
	$(CC) -E $(CFLAGS) -M $(SRCS) > .depend

clean: 
	$(RM) -f $(OBJS) $(DIRLIB) .depend
	$(MAKE) -C test clean

test:
	$(MAKE) -C test

FORCE:

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
