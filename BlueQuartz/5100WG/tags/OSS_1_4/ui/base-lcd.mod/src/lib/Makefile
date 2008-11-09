#
# Makefile for creating LED and LCD utilities
#
SRCS = lcdlock.c lcdlocale.c lcdtimeout.c lcddisplay.c lcdread.c \
       lcdcursor.c lcdlink.c lcdnet.c lcdpass.c
OBJS := $(SRCS:.c=.o)
LIB = liblcd.a

RANLIB = ranlib

DEBUG  = -g -Wall
COPTS  = -O2
CFLAGS = $(DEBUG) $(COPTS) -I../

all:	$(LIB)

$(OBJS): ../lcdutils.h

$(LIB): $(OBJS)
	$(AR) crv $(LIB) $(OBJS)
	$(RANLIB) $(LIB)

clean:
	-rm -f $(OBJS) $(LIB) *~ TAGS

