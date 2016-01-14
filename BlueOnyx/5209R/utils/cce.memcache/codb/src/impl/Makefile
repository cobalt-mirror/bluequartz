# $Id: Makefile,v 1.2 2001/08/10 22:23:10 mpashniak Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../../../dynamic.mk

INCLUDES += -Iinclude -Ilib -I../../include

SRCS = \
	connect.c \
	file.c \
	list.c \
	misc.c \
	scalar.c \
	index.c \
	oid.c \

OBJS = $(patsubst %.c,%.o,$(SRCS))

all: lib/libdb.a lib/db.h impl.o

lib/libdb.a:
	$(MAKE) -C lib

lib/db.h:
	$(MAKE) -C lib

impl.o: $(OBJS)
	ld -r -o $@ $(OBJS) -Llib -ldb

clean:
	$(MAKE) -C lib clean
	rm -f $(OBJS) impl.o

