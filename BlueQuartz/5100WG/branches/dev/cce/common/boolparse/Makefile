# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

DIRMK_SRCS = bool.tab.c lex.bool.c
DIRMK_CLEAN = myclean

include ../../dynamic.mk
include $(CCE_TOPDIR)/dirmake.mk

CFLAGS += -Wno-unused
# If you turn on DO_YYDEBUG, also add '-t' to YFLAGS
#CFLAGS += -DDO_YYDEBUG

YFLAGS = -d -b bool -p bool
# If you turn on -t, also add DO_YYDEBUG to CFLAGS
#YFLAGS += -vtl

LFLAGS = -Pbool
#LFLAGS += -d

bool.tab.c: bool_parse.y
	$(YACC) $(YFLAGS) $<

lex.bool.c: bool_lex.l bool.tab.h
	$(LEX) $(LFLAGS) $<

myclean:
	$(RM) lex.bool.c bool.tab.c bool.tab.h
