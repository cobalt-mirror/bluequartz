# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

DIRMK_SRCS = odb_types.c \
	odb_attribs.c	\
	odb_helpers.c \
	codb2_glue.c \
	codb_find.c \
	codb_events.c \
	security.c \
	compare.c
DIRMK_DIRS = impl txn
include ../../dynamic.mk
include $(CCE_TOPDIR)/dirmake.mk

DEFS += -DCODB_INTERNAL

test: 
	$(MAKE) -C test test
