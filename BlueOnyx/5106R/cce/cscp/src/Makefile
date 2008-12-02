# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../../dynamic.mk
ifeq (../cscp_ver.mk,$(wildcard ../cscp_ver.mk))
include ../cscp_ver.mk
endif

INCLUDES += -I../include
DEFS += -DCSCP_V_MAJ=$(CSCP_V_MAJ) -DCSCP_V_MIN=$(CSCP_V_MIN) \
	-DCSCP_VERSION="\"$(CSCP_VERSION)\""
SRCS = \
	lex.cscp_cmd.c \
	cscp_cmd_table.c \
	cscp_fsm.c \
	cscp_msgs.c \
	cscp_parse.c \
	cscp_auth.c \
	cscp_write.c
OBJS = $(patsubst %.c,%.o,$(SRCS))
DIRLIB = ../cscp.a

all: $(DIRLIB)

$(DIRLIB): $(OBJS)
	$(AR) rcs $@ $^

lex.cscp_cmd.c: cscp_cmd.l
	$(LEX) -o$@ $^

lex.cscp_cmd.o: lex.cscp_cmd.c
	$(CC) $(CFLAGS) -Wno-unused -c -o $@ $<

depend: .depend
.depend: $(SRCS)
	$(CC) -E $(CFLAGS) -M $(SRCS) > .depend

clean:
	$(RM) -f .depend
	$(RM) -f $(DIRLIB) $(OBJS) 
	$(RM) -f lex.cscp_cmd.c

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
