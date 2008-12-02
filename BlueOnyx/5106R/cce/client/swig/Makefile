# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# Makefile for swig module creation.
# May later do perl API creation as well as python.
#

include ../../rules.mk

include /usr/lib/swig_lib/Makefile

SWIG=swig
SWIGOPT=-v -I../include/ -shadow
TARGET=cce
INTERFACE=cce.i

INCLUDE= -g \
	-I../include/ \
	-I../../cscp/include/ \
	-I../../codb/include/ \
	-I../../conf/include/ \
	-I../../scalar/include/ \
	-I../../../devel-tools/libdebug/include/ \
	-I../../../i18n/src/include/ \
	`glib-config --cflags` 

LIBS= \
	-lcce -L../src/
SRCS=
TARGET=cce
INTERFACE=cce.i

all:: python

clean:
	rm -f *_wrap* *.o mypython code *.so *.sl *.pm

