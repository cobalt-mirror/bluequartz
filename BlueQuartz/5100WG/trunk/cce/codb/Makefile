# $Id: Makefile 3 2003-07-17 15:19:15Z will $

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

.EXPORT_ALL_VARIABLES:

CODBDIR := $(shell pwd)

CC = gcc
INCLUDES = \
	-I$(CODBDIR)/include \
	-I$(CODBDIR)/../include \
	-I$(CCEINCDIR) \
	-I$(CODBDIR)/src/impl/include
LIBDIRS = \
	-L$(CODBDIR)/.. \
	-L$(CCELIBDIR)
LIBS = \
	$(LIBDIRS) \
	-lcce_common \
	`glib-config --libs` \
	-ldebug
DEFS = -DCODB_INTERNAL
DEFDEBUG = -ggdb -Wall $(DEBUG)
CFLAGS = -O2 $(DEFS) $(INCLUDES) `glib-config --cflags` $(DEFDEBUG)

LIB = libcodb.a
OBJS = src/codb.o

all: $(LIB)

$(LIB): $(OBJS)
	make -C src
	ar rcs $@ $^
	ranlib $@

$(OBJS): FORCE
	make -C src

debug:
	make all DEBUG="-DDEBUG_CODB"

install: 
	# nothing to install

uninstall:
	# nothing to uninstall

setup:
	# nothing to setup

FORCE:
	
	
.PHONY: test
test: $(OBJS)
	make -C src test

clean:
	/bin/rm -f `find . -type f -name \*.o -print`
	/bin/rm -f `find . -type f -name core`
	/bin/rm -rf `find . -type d -name codb -print`
	/bin/rm -rf `find . -type f -name codb.oids -print`
	/bin/rm -f .depend
	/bin/rm -f $(LIB)*
	make -C src clean

depend:
	make -C src depend

# dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
