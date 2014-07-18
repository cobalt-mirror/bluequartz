# $Id: libs.mk 3 2003-07-17 15:19:15Z will $
# This is provided as a simplified and extracted version of what rules.mk does.
# You can't use them at the same time, probably.  I'd have cleaned up rules.mk, 
# but I didn't know what would break, so I didn't.  It should be cleared up 
# eventually.

# This file provides the following rules:
#	static-lib
#	shared-lib
#	both-libs
#	install-static-lib
#	install-shared-lib
#	install-both-libs
#	libclean
#
# These rules all assume you have defined the following:
#	LIBNAME (including "lib" prefix)
#	LIB_V_MAJOR (for shared only)
#	LIB_V_MINOR (for shared only, optional)
#	ST_LIBSRCS (for static only)
#	SH_LIBSRCS (for shared only)
#	ST_LIBDIR (for static install only)
#	SH_LIBDIR (for shared install only)
#	ST_DEPS (for static only, optional)
#	SH_DEPS (for shared only, optional)
#

# the basics
RANLIB = ranlib
AR = ar
CC = gcc
INSTALL = install
INSTFLAGS = -o root -g root
LDCONFIG = ldconfig


# common cflags for a lib
ST_SH_COMMON_CFLAGS = -D_REENTRANT -Wno-error

# flags for building static libs
ST_CFLAGS = $(CFLAGS) $(ST_SH_COMMON_CFLAGS)
ST_LDFLAGS = $(LDFLAGS)

# flags for building shared libs
SH_CFLAGS = $(CFLAGS) $(ST_SH_COMMON_CFLAGS) -fPIC
SH_LDFLAGS = $(LDFLAGS) -shared -rdynamic

ifndef LIB_V_MINOR
LIB_V_MINOR = 0
endif


#
# target file names
#
ST_LIBOBJS = $(ST_LIBSRCS:.c=.o)
SH_LIBOBJS = $(SH_LIBSRCS:.c=.lo)

ST_LIB = $(LIBNAME).a
SH_LIB = $(LIBNAME).so
SH_LIB_MAJ = $(SH_LIB).$(LIB_V_MAJOR)
SH_LIB_FULL = $(SH_LIB_MAJ).$(LIB_V_MINOR)


#
# suffix rules
#
.c.o:
	$(CC) $(ST_CFLAGS) -c $< -o $@

.c.lo: 
	$(CC) $(SH_CFLAGS) -c $< -o $@

.SUFFIXES: .lo

#
# how to build the static library
#
$(ST_LIB): $(ST_LIBOBJS) $(ST_DEPS)
	@if [ -z '$(LIBNAME)' ]; then \
		echo "LIBNAME not specified."; \
		false; \
	fi
	$(AR) crv $@ $(ST_LIBOBJS)
	$(RANLIB) $@

#
# how to build the shared library (.so.vmaj.vmin)
#
$(SH_LIB_FULL): $(SH_LIBOBJS) $(SH_DEPS)
	@if [ -z '$(LIBNAME)' ]; then \
		echo "LIBNAME not specified."; \
		false; \
	fi
	$(CC) -Wl,-soname,$(SH_LIB_MAJ) $(SH_LIBOBJS) -o $@ $(SH_LDFLAGS)
	-ln -sf $@ $(SH_LIB_MAJ)
	-ln -sf $@ $(SH_LIB)


#
# easy to remember rules
#
.PHONY: static-lib shared-lib both-libs
.PHONY: install-static-lib install-shared-lib install-both-libs
.PHONY: libclean

static-lib: $(ST_LIB)

shared-lib: $(SH_LIB_FULL)

both-libs: static-lib shared-lib

install-static-lib: $(ST_LIB)
	mkdir -p $(ST_LIBDIR)
	$(INSTALL) $(INSTFLAGS) -m 644 $(ST_LIB) $(ST_LIBDIR)

install-shared-lib: $(SH_LIB_FULL)
	mkdir -p $(SH_LIBDIR)
	$(INSTALL) $(INSTFLAGS) -m 755 $(SH_LIB_FULL) $(SH_LIBDIR)
	ln -sf $(SH_LIB_FULL) $(SH_LIBDIR)/$(SH_LIB_MAJ)
	ln -sf $(SH_LIB_MAJ) $(SH_LIBDIR)/$(SH_LIB)
	$(LDCONFIG)

install-both-libs: install-static-lib install-shared-lib

libclean:
	rm -f $(ST_LIBOBJS) $(SH_LIBOBJS)
	rm -f $(ST_LIB) $(SH_LIB)*
