# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

DIRMK_SRCS = handler_exec.c \
	handler_perl.c \
	handler_test.c \
	cce_ed.c
include ../../dynamic.mk
include $(CCE_TOPDIR)/dirmake.mk

test:
	$(MAKE) -C test test
