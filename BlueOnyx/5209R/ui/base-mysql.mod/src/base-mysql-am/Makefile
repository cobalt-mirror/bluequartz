# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#

#ifdef TOPDIR
#include $(TOPDIR)/devel/defines.mk
#else
include /usr/sausalito/devel/defines.mk
#endif

BINDIR = $(SWATCHBINDIR)
RPMDIR = $(SWATCHBINDIR)/hotfix
BINS = am_mysql.exp check-mysql.sh
GRPMS = mariadb-galera-common-5.5.40-4.el7.x86_64.rpm mariadb-galera-server-5.5.40-4.el7.x86_64.rpm galera-25.3.5-7.el7.x86_64.rpm

.PHONY: all
all: install

install: $(BINS)
	mkdir -p $(BINDIR)
	install -m 750 -o root -g root $(BINS) $(BINDIR)
	mkdir -p $(RPMDIR)
	install -m 644 -o root -g root $(GRPMS) $(RPMDIR)
