############################################################################
# Copyright 2001 Sun Microsystems, Inc. All rights reserved.
#
# $Id: Makefile,v 1.1 2001/09/18 16:59:38 jthrowe Exp $
#
# Makefile for paflogd and pafalertd
# Original Author: Ge' Weijers
#
# IMPORTANT NOTE!
#    all environmental variables here are prepended with a "P" to avoid 
#    conflicts with those defined in the filter make script.  This is 
#    important to avoid during an RPM build.
#
############################################################################


all: newversion paflogd pafalertd

##
# Flags
##

SHELL     = /bin/sh

# See http://pauillac.inria.fr/~xleroy/linuxthreads/faq.html
# Errno needs to be local to each thread.
REENTRANT = -D_REENTRANT

PCC       = g++

# -g and -O are mutually exclusive
# Pass in "DEBUG=-DDEBUG" to compile in '#ifdef DEBUG' chunks
#PCFLAGS   = $(DEBUG) $(REENTRANT) -g -Wall -I../commonlib

# For final builds
PCFLAGS   = $(REENTRANT) -O -Wall -I../commonlib


##
# Sources and definitions
##

PCSOURCES = AlertServer.cpp AlertServerConstants.cpp  AlertSettings.cpp AlertSocketThread.cpp LogServer.cpp LogServerThread.cpp LogSettings.cpp NotifierThread.cpp ObjectSocketThread.cpp ReaderThread.cpp WriterThread.cpp

PINCLUDE  = AlertServer.h AlertSettings.h AlertSocketThread.h LogSettings.h LogServer.h LogServerThread.h NotifierThread.h ObjectSocketThread.h ReaderThread.h WriterThread.h

ALLSOURCES = $(PCSOURCES) $(PINCLUDE) Makefile

PALERTOBJECTS = AlertServer.o AlertServerConstants.o AlertSettings.o AlertSocketThread.o NotifierThread.o ObjectSocketThread.o ReaderThread.o

PLOGOBJECTS = LogServer.o LogServerThread.o LogServerConstants.o LogSettings.o LogSocketThread.o ObjectSocketThread.o ReaderThread.o WriterThread.o 

# On CCE architectures include CCE libs
# This will be improved and will probably use an envar
ifeq ($(wildcard /usr/sausalito/lib),/usr/sausalito/lib)
CCE_LIB_PATH   := -L/usr/sausalito/lib
CCE_LIBS       := `glib-config --libs` -lcce -li18n
endif

LIB_PATH   := -L../commonlib $(CCE_LIB_PATH)
LIBS       := -lphoenix -lpthread $(CCE_LIBS)

TESTS=AlertServer.test LogServer.test

PDEPEND   = .depend

########################################
# Gentimes

GENTIMES = gentimes
GCSOURCE = gentimes.c

newversion: $(GCSOURCE:.c=.o)
	# ******************** Paflogd new version 
	$(PCC) $(PCFLAGS) $(GCSOURCE:.c=.o) -o $(GENTIMES)
	./$(GENTIMES) -v > version.h

########################################
# Install

# RPM_BUILD_ROOT is in the environment (rpm sets it)

install: paflogd pafalertd
	# ******************** Paflogd Install
	install -d $(RPM_BUILD_ROOT)/usr/sbin
	install -m 555 paflogd $(RPM_BUILD_ROOT)/usr/sbin
	install -m 555 pafalertd $(RPM_BUILD_ROOT)/usr/sbin

########################################
# Build

../commonlib/libphoenix.a: FOO
	cd ../commonlib ; make libphoenix.a

paflogd: $(PDEPEND) $(PLOGOBJECTS) ../commonlib/libphoenix.a
	# ******************** Paflogd BUILD
	$(PCC) $(PCFLAGS) $(PLOGOBJECTS) $(LIB_PATH) $(LIBS) -o $@

pafalertd: $(PDEPEND) $(PALERTOBJECTS) ../commonlib/libphoenix.a
	# ******************** Pafalertd BUILD
	$(PCC) $(PCFLAGS) $(PALERTOBJECTS) $(LIB_PATH) $(LIBS) -o $@

########################################
# Testing

AlertServer.test:	AlertServer.test.o ../commonlib/libphoenix.a AlertServerConstants.o AlertSettings.o
	$(PCC) $(PCFLAGS) -o AlertServer.test AlertServer.test.o AlertServerConstants.o AlertSettings.o -L../commonlib -lphoenix -lpthread

LogServer.test: FOO AlertSettings.o LogServer.test.o LogServerThread.o LogServerConstants.o LogSettings.o 
	@echo
	@echo "Please use: make $@ DEBUG=-DDEBUG"
	@echo
	$(PCC) $(PCFLAGS) -o LogServer.test AlertSettings.o LogServer.test.o LogServerThread.o LogServerConstants.o LogSettings.o -L../commonlib -lphoenix -lpthread

########################################

btar:
	tar cvzf paflogd.tar.gz *.h *.cpp

source:  paflogd-src.tar

paflogd-src.tar:  $(ALLSOURCES) 
	tar cf $@ $(ALLSOURCES)

########################################

# for clean
EMACSTEMP=$(ALLSOURCES:=~) 
OTHERJUNK=gmon.out core 

# clean:	FOO
# 	-rm -f paflogd $(GENTIMES) $(POBJECTS) $(PDEPEND) $(TARGET) \
# 				$(EMACSTEMP) $(OTHERJUNK)

clean:	
	rm -f paflogd pafalertd *.o *~ $(GENTIMES) .depend

########################################

#
# Depend
#
$(PDEPEND):	$(ALLSOURCES)
	$(PCC) -MM $(PCFLAGS) $(PCSOURCES) AlertServer.cpp LogServer.cpp > $@

#
# Suffix rules for .c files to .o files
#
.cpp.o:
	$(PCC) $(PCFLAGS) -c $<

.c.o:
	$(PCC) $(PCFLAGS) -c $<


########################################

FOO:

$(POBJECTS):    Makefile

.SUFFIXES:	.c .h .o

#
# Include dependencies file
#
include $(PDEPEND)
