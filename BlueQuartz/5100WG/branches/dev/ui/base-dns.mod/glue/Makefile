# $Id: Makefile 201 2003-07-18 19:11:07Z will $
# Makefile to whip some files into /etc
#

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

SCRIPTS=named_enable.pl

INSTALL = /usr/bin/install -m 0644 -o root
PWD = $(shell pwd)

all:

install:
	mkdir -p $(CCESBINDIR)
	(cd sbin; $(INSTALL) $(INSTALL_BINFLAGS) $(SCRIPTS) $(CCESBINDIR) )

rpm:

