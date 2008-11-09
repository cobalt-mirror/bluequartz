# Makefile for libcommon inside CCE
# $Id: Makefile 3 2003-07-17 15:19:15Z will $

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

.EXPORT_ALL_VARIABLES:

TOPDIR := $(shell pwd)

CC = gcc
DEFS = 
DEFDEBUG = -ggdb -Wall $(DEBUG)
INCLUDES = -I. -I$(TOPDIR)/include -I$(TOPDIR)/../include -I$(CCEINCDIR)
CFLAGS = -O2 `glib-config --cflags` $(DEFS) $(INCLUDES) $(DEFDEBUG)

LIB = $(TOPDIR)/libcommon.a

all: 
	make -C src

debug:
	make all DEBUG="-DDEBUG_COMMON"

# test stuff
.PHONY: test
test:
	# nothing to test

install: all
	# nothing to install

uninstall:
	# nothing to uninstall

setup:
	# nothing to setup
	
depend:  
	make -C src depend

clean:
	make -C src clean
	rm -f $(LIB)
	rm -f .depend core
