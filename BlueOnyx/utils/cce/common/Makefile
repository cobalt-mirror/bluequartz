# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Makefile for libcommon inside CCE

include ../dynamic.mk

all: 
	$(MAKE) -C src all

debug:
	$(MAKE) all DEBUG="$(CCE_DEBUG) -DDEBUG_COMMON"

depend:  
	$(MAKE) -C src depend

test:
	$(MAKE) -C test

clean:
	$(MAKE) -C src clean
	$(MAKE) -C test clean
