# Makefile for libcscp
# $Id: Makefile 3 2003-07-17 15:19:15Z will $

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

.EXPORT_ALL_VARIABLES:

CSCP_V_MAJ = 0
CSCP_V_MIN = 80
CSCP_VERSION = $(CSCP_V_MAJ).$(CSCP_V_MIN)

CSCPDIR := $(shell pwd)

CC = gcc
DEFS = -DCSCP_V_MAJ=$(CSCP_V_MAJ) -DCSCP_V_MIN=$(CSCP_V_MIN) \
	-DCSCP_VERSION="\"$(CSCP_VERSION)\""
DEFDEBUG = -ggdb -Wall $(DEBUG)
INCLUDES = \
	-I. \
	-I$(CSCPDIR)/include \
	-I$(CSCPDIR)/../include \
	-I$(CCEINCDIR)
CFLAGS = -O2 `glib-config --cflags` $(DEFS) $(INCLUDES) $(DEFDEBUG)

LIB = libcscp.a
TESTS = tmp/test_cscp_parse

all: 
	make -C src
	cp src/$(LIB) .
libclean:
	rm -f $(LIB)

debug:
	make all DEBUG="-DDEBUG_CSCP"

# test stuff
.PHONY: test tmp
test: testclean tmp $(TESTS)

tmp:
	mkdir -p tmp

tmp/test_cscp_parse: test/test_cscp_parse.o $(LIB)
	$(CC) -o $@ $^ `glib-config --libs` -lfl -L../scalar -lcce_scalar
	$@ < test/test_cscp_parse.in.1 > tmp/test_cscp_parse.out 2>/dev/null
	@diff -u test/test_cscp_parse.out.1 tmp/test_cscp_parse.out >/dev/null 2>&1 || (echo TEST FAILED && false)
	@echo TEST SUCCEEDED

install:
	# nothing to install

uninstall:
	# nothing to uninstall

setup:
	# nothing to setup
	
depend:  
	make -C src depend

clean: testclean
	make -C src clean
	rm -f test/*.o
	rm -f $(LIB)
	rm -f .depend core

testclean:
	rm -rf tmp
	rm -f test/*.o
	rm -f $(TESTS)
