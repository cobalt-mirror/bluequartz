# $Id: Makefile 1382 2010-02-21 12:11:21Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Makefile for common src

include ../../dynamic.mk
CFLAGS += -D_GNU_SOURCE

SRCS = \
	argparse.c \
	csem.c \
	tcp_socket.c \
	ud_socket.c \
	stresc.c \
	xml_parse.c \
	cce_debug_mask.c \
	intspan.c
OBJS = $(patsubst %.c,%.o,$(SRCS))
DIRLIB = ../common.a

all: $(DIRLIB)

$(DIRLIB): $(OBJS) boolparse/boolparse.o
	$(AR) rcs $@ $^

depend: .depend
.depend: $(SRCS)
	$(CC) -E $(CFLAGS) -M $(SRCS) > .depend

clean:
	$(RM) -f .depend $(OBJS) $(DIRLIB)
	$(MAKE) -C boolparse clean

boolparse/boolparse.o: FORCE
	$(MAKE) -C boolparse

FORCE:

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
