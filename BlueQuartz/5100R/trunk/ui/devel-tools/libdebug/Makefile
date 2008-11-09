# Makefile for libdebug.a

include ../rules/defines.mk

DEBUG = -ggdb -DDEBUG -DUSE_LIBDEBUG=1 -DUSE_MEMDEBUG=1
DEFS = -DLIBDEBUG_INTERNAL
INCLUDES = -I./include/
CFLAGS = -O2 -Wall -I./include `glib-config --cflags` $(DEBUG) $(DEFS)

CCELIBSRC = memdebug.c init.c
CCEDEPSRC = $(CCELIBSRC)
CCELIBNAME = libdebug
HEADERS = include/libdebug.h

all: dep lib

install: install_lib 
	-mkdir -p $(CCEINCDIR)
	$(INSTALL) $(INSTALL_HEADERFLAGS) $(HEADERS) $(CCEINCDIR)

debug: all

uninstall:
	rm -f $(CCELIBDIR)/$(LIB)
	rm -f $(CCEINCDIR)/libdebug.h

setup:
	# nothing to setup

test:
	# no tests here 

$(OBJS): Makefile

include ../rules/rules.mk
