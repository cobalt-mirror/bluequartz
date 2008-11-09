ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

include ../../version.mk

LIBNAME = libcce
LIB_V_MAJOR = $(CCE_V_MAJ)
LIB_V_MINOR = $(CCE_V_MIN)
LIBSRCS = c6.c cce.c
ST_LIBSRCS = $(LIBSRCS)
SH_LIBSRCS = $(LIBSRCS)
ST_LIBDIR = $(CCELIBDIR)
SH_LIBDIR = $(CCELIBDIR)
SH_DEPS = ../../libcce_common.a

DEFS = -DVERSION="\"$(CCE_VERSION)\""
LIBDIRS = -L../.. -L$(CCELIBDIR) -L/usr/sausalito/lib
LIBS = $(LIBDIRS) -ldebug `glib-config --libs` -lcce_common
INCLUDES = -I. -I../include -I../../include -I$(CCEINCDIR) -I/usr/sausalito/include
DEFDEBUG = -ggdb -Wall $(DEBUG)
CFLAGS = -O2 $(DEFS) $(INCLUDES) `glib-config --cflags` $(DEFDEBUG)
LDFLAGS = -Wl,-rpath,$(CCELIBDIR) $(LIBS)

HEADERS = ../include/c6.h ../include/cce.h
INCLUDEDIR = $(CCEINCDIR)/cce/

#C6TESTS = $(C6TEST1) $(C6TEST2)
#C6TEST1 = test/test.1
#C6TEST2 = test/test.2
#C6TEST1SRC = test/test.c
#C6TEST2SRC = test/test_handler.c

all: depend both-libs

install: install-both-libs install_headers 

install_headers: $(HEADERS)
	mkdir -p $(INCLUDEDIR)
	install -o root -g root -m 644 $(HEADERS) $(INCLUDEDIR)

clean: libclean #testclean
	find . -type f -name \*.o  | xargs rm -f

testclean:
#	/bin/rm -f $(C6TESTS)


debug:
	make all DEBUG="-DDEBUG"
#test: debug_all $(C6TESTS)

#$(C6TEST1): $(C6TEST1SRC) $(LIB)
#	$(CC) $(CFLAGS) -DDEBUG -g -o $(C6TEST1) $^ -L./ \
#		-Wl,-rpath,/usr/sausalito/lib libcce.a $(LIBS)

#$(C6TEST2): $(C6TEST2SRC) $(LIB)
#	$(CC) $(CFLAGS) -DDEBUG -g -o $(C6TEST2) $^ -L./ \
#		-Wl,-rpath,/usr/sausalito/lib libcce.a $(LIBS)

#test/test_findusers: test/test_findusers.c $(LIB)
#	$(CC) $(CFLAGS) -DDEBUG -g -o test/test_findusers $^ -L./ \
#		-Wl,-rpath,/usr/sausalito/lib libcce.a $(LIBS)

depend: .depend

.depend: $(LIBSRCS)
	$(CC) -E $(CFLAGS) -M $@ > $@

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/libs.mk
else
include $(CCEDIR)/devel/libs.mk
endif

# add dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif

