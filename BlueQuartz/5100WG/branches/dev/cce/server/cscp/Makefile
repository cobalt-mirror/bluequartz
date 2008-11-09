# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

DIRMK_SRCS = cscp_cmd_table.c \
	cscp_fsm.c \
	cscp_msgs.c \
	cscp_auth.c \
	cscp_write.c \
	cscp.tab.c \
	lex.cscp.c
DIRMK_CLEAN = myclean
include ../../dynamic.mk
include $(CCE_TOPDIR)/dirmake.mk

CSCP_V_MAJOR = 0
CSCP_V_MINOR = 80

CFLAGS += -Wno-unused
DEFS += -DCSCP_V_MAJOR=$(CSCP_V_MAJOR) -DCSCP_V_MINOR=$(CSCP_V_MINOR) \
	-DCSCP_VERSION="\"$(CSCP_VERSION)\""

lex.cscp.c: cscp.l
	$(LEX) -Pcscp $^

cscp.tab.c cscp.tab.h: cscp.y
	$(YACC) -v -d -b cscp -p cscp $^

test: 
	$(MAKE) -C test test

myclean:
	$(RM) lex.cscp.c cscp.tab.[ch] cscp.output
