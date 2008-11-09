# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

DIRMK_SRCS = argparse.c csem.c tcp_socket.c ud_socket.c stresc.c intspan.c \
	g_hashwrap.c props.c cce_debug_mask.c xml_parse.c oom.c
DIRMK_DIRS = boolparse
DIRMK_ALL = libcommon.a
DIRMK_CLEAN = myclean

include ../dynamic.mk
include $(CCE_TOPDIR)/dirmake.mk

libcommon.a: common.a
	ln -s $< $@

test: all
	$(MAKE) -C test test

myclean:
	$(RM) libcommon.a
