# $Id: Makefile,v 1.2 2001/11/13 19:54:04 bservies Exp $
#
# Copyright Sun Microsystems, Inc.  All rights reserved.
#
ifndef CCETOPDIR
CCETOPDIR=/usr/sausalito
endif

include $(CCETOPDIR)/devel/defines.mk

INITDIR=$(PREFIX)/etc/rc.d/init.d
INIT_SCRIPTS = init/cobalt-arkeia

all: 

clean: 

install: $(INIT_SCRIPTS)
 
.PHONY: $(INIT_SCRIPTS)
$(INIT_SCRIPTS)::
	mkdir -p $(INITDIR)
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) $@ $(INITDIR)
