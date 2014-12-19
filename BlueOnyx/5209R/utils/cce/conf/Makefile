# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Makefile for cce_conf

include ../dynamic.mk

INCLUDES += -I./include
SRCS = \
	src/conf.c \
	src/conf_handler.c
OBJS = $(patsubst %.c,%.o,$(SRCS))
DIRLIB = conf.a

all: $(DIRLIB)

$(DIRLIB): $(OBJS)
	$(AR) rcs $(DIRLIB) $(OBJS)

debug:
	$(MAKE) all DEBUG="$(CCE_DEBUG)"

depend: .depend
.depend: $(SRCS)
	$(CC) -E $(CFLAGS) -M $(SRCS) > .depend

test: 
	$(MAKE) -C test

clean: 
	$(RM) -f $(DIRLIB) $(OBJS) .depend
	$(RM) -f src/conf_parser.c
	$(MAKE) -C test clean

doc: include/cce_conf.h
	$(RM) -rf html
	doc++ -H -a -f -M -j -d html $^

#dependencies
ifeq (.depend,$(wildcard .depend))
include .depend
endif
