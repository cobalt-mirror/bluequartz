# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Makefile for libcscp

include ../dynamic.mk

CSCP_V_MAJ = 0
CSCP_V_MIN = 99

INCLUDES += -I./include

all: cscp_ver.mk
	$(MAKE) -C src

cscp_ver.mk: FORCE
	@( \
		echo "CSCP_V_MAJ=$(CSCP_V_MAJ)"; \
		echo "CSCP_V_MIN=$(CSCP_V_MIN)"; \
		echo "CSCP_VERSION=$(CSCP_V_MAJ).$(CSCP_V_MIN)"; \
	) > $@

debug:
	$(MAKE) all DEBUG="$(CCE_DEBUG) -DDEBUG_CSCP"

depend:  
	$(MAKE) -C src depend

test:
	$(MAKE) -C test

clean: 
	$(MAKE) -C src clean
	$(MAKE) -C test clean
	$(RM) -f cscp_ver.mk

FORCE:
