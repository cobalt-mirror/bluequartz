# $Id: rules.mk,v 1.8 2001/08/10 22:23:08 mpashniak Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

# general rules for building CCE

# if this was included by toplevel Makefile, CCE_TOPDIR exists
# if this was included by dynamic.mk, CCE_TOPDIR exists
# else generate an error
ifndef CCE_TOPDIR
include CCE_TOPDIR
endif

#
# directory names
#

# change PREFIX and CCE_INSTALLDIR to suit your needs
ifndef PREFIX
PREFIX =
endif
CCE_INSTALLDIR = $(PREFIX)/usr/sausalito

# you shouldn't need to change these
CCE_BINDIR = $(CCE_INSTALLDIR)/bin
CCE_SBINDIR = $(CCE_INSTALLDIR)/sbin
CCE_SCHEMADIR = $(CCE_INSTALLDIR)/schemas
CCE_CONFDIR = $(CCE_INSTALLDIR)/conf
CCE_CODBDIR = $(CCE_INSTALLDIR)/codb
CCE_CONSTRUCTORDIR = $(CCE_INSTALLDIR)/constructor
CCE_DESTRUCTORDIR = $(CCE_INSTALLDIR)/destructor
CCE_HANDLERDIR = $(CCE_INSTALLDIR)/handlers
CCE_INCLUDEDIR = $(CCE_INSTALLDIR)/include
CCE_LIBDIR = $(CCE_INSTALLDIR)/lib
CCE_PERLDIR = $(CCE_INSTALLDIR)/perl
CCE_SESSIONDIR = $(CCE_INSTALLDIR)/sessions

SYS_INITDIR = $(PREFIX)/etc/rc.d/init.d
SYS_PAMDIR = $(PREFIX)/etc/pam.d
SYS_APACHELIBDIR = $(PREFIX)/usr/lib/apache

# install flags
INST_DEFAULT = -o root -g root
INST_SBINFLAGS = $(INST_DEFAULT) -m 700
INST_BINFLAGS = $(INST_DEFAULT) -m 755
INST_FILEFLAGS = $(INST_DEFAULT) -m 644

# all toplevel Makefiles should have these rules:
.PHONY: all debug depend test clean

# general use definitions
CC = gcc
LD = ld
AR = ar
RM = rm
LEX = flex
MAKE = make
RANLIB = ranlib
INSTALL = install
LDCONFIG = /sbin/ldconfig

# macros useful for sub-Makefiles
CCE_CFLAGS = -ggdb -dH -Wcast-qual -Werror `glib-config --cflags`  -D_GNU_SOURCE
CCE_INCLUDES = -I$(CCE_TOPDIR)/include
#CCE_DEBUG = -DDEBUG -O0 -ggdb -dH
CCE_DEBUG = -DDEBUG -ggdb -dH
CCE_LIBS = -L$(CCE_TOPDIR) -lcce_common `glib-config --libs` -lpam -lfl

# sane defaults
CFLAGS = $(CCE_CFLAGS) $(INCLUDES) $(DEBUG) $(DEFS)
LIBS = $(CCE_LIBS)
INCLUDES = $(CCE_INCLUDES)
LDFLAGS = 
DEBUG = 
DEFS = 

RPMBUILD=$(shell which rpmbuild>/dev/null 2>&1&&echo rpmbuild||echo rpmbuild)
USER_HTTPD=admserv

