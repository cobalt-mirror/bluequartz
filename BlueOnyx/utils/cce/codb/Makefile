# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../dynamic.mk

DIRLIB = codb.a
OBJS = src/codb.o

all: $(DIRLIB) 

$(DIRLIB): $(OBJS)
	$(AR) rcs $(DIRLIB) $(OBJS)

$(OBJS): FORCE
	$(MAKE) -C src all

debug:
	$(MAKE) all DEBUG="$(CCE_DEBUG) -DDEBUG_CODB"

depend:
	$(MAKE) -C src depend
	
test:
	$(MAKE) -C test

clean:
	$(MAKE) -C src clean
	$(MAKE) -C test clean
	$(RM) -f $(DIRLIB)

FORCE:

# dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
