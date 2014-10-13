# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# This is a header to make a dunamic PHP extension for cce.
#

include ../../dynamic.mk

# FIXME: To install this you will need to manually copy dl/phpdl.h from your
# PHP source tree into /usr/local/include/php/

CCEMODULE = cce-libcce

all:
	make -C src

clean:
	make -C src clean

depend:

install:
	make -C src install
	make -C class install
