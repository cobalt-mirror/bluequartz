# $Id: Makefile,v 1.2 2001/08/10 22:23:16 mpashniak Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../../../dynamic.mk

CFLAGS = -I../../../include

# If you turn on DO_YYDEBUG, also add '-t' to YFLAGS
#CFLAGS += -DDO_YYDEBUG

SRCS = bool_parse.tab.c bool_lex.c
OBJS = $(SRCS:.c=.o)

CLEANFILES = bool_parse.tab.h bool_lex.c bool_parse.c bool_parse.tab.c


# If you turn on -t, also add DO_YYDEBUG to CFLAGS
YFLAGS=-d
#YFLAGS=-vtdl
YACC=bison

LFLAGS=-t -d
LFLAGS=-t

.c.o:
	$(CC) $(CFLAGS) -c $<

all: boolparse.o

bool_parse.tab.c: bool_parse.y
	$(YACC) $(YFLAGS) bool_parse.y

bool_lex.c: bool_lex.l bool_parse.tab.h
	$(LEX) $(LFLAGS) $< > $@

boolparse.o: $(OBJS)
	$(LD) -r -o $@ $^

clean:
	-/bin/rm -f $(CLEANFILES) $(OBJS) boolparse.o
