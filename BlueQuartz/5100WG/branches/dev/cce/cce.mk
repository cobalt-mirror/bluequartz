# $Id: cce.mk 229 2003-07-18 20:22:20Z will $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

# general rules for building CCE

# if this was included by toplevel Makefile, CCE_TOPDIR exists
# if this was included by dynamic.mk, CCE_TOPDIR exists
# else generate an error
ifndef CCE_TOPDIR
include CCE_TOPDIR
endif

# general use definitions
CC = gcc
LEX = flex
YACC = bison
MAKE = gmake
RANLIB = ranlib
INSTALL = install

CCE_CFLAGS += -ggdb `glib-config --cflags`
CCE_WARNS += -Wall -Wcast-qual -Wpointer-arith -Werror 
CCE_INCLUDES += -I$(CCE_TOPDIR)/include
CCE_LIBS += `glib-config --libs` -L$(CCE_TOPDIR)/common
CCE_DEBUG += -DDEBUG -O0
CCE_DEFS += 

CFLAGS = $(CCE_CFLAGS) $(WARNS) $(INCLUDES) $(DEBUG) $(DEFS)
WARNS = $(CCE_WARNS)
INCLUDES = $(CCE_INCLUDES)
LIBS = $(CCE_LIBS)
DEBUG = 
DEFS = 

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

SYS_ETCDIR = $(PREFIX)/etc
SYS_INITDIR = $(PREFIX)/etc/rc.d/init.d
SYS_PAMDIR = $(PREFIX)/etc/pam.d
SYS_APACHELIBDIR = $(PREFIX)/usr/lib/apache

# install flags
INSTALL_DEFAULT = $(INSTALL) -o root -g root
INSTALL_SBIN = $(INSTALL_DEFAULT) -m 700
INSTALL_BIN = $(INSTALL_DEFAULT) -m 755
INSTLL_FILE = $(INSTALL_DEFAULT) -m 644

# all toplevel Makefiles should have these rules:
.PHONY: all debug depend test clean install uninstall

