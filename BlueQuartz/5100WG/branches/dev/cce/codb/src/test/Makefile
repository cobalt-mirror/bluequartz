fall:
	@echo ERROR this makefile is obsolete, use ../Makefile
	@/bin/false

# $Id: Makefile 3 2003-07-17 15:19:15Z will $

CC = gcc
INCLUDES = -I../../include -I../../../libscalar/include \
	-I../../../conf/include -I../../../../libdebug/include
LIBDIRS = -L../../../libscalar -L../../../conf -L../../../../libdebug
LIBS = $(LIBDIRS) -lcce_scalar -lcce_conf `glib-config --libs` -ldebug
DEFS = 
DEBUG = -ggdb #-DUSE_LIBDEBUG=1 #-pedantic
CFLAGS = -O2 -Wall $(INCLUDES) $(DEFS) $(DEBUG) `glib-config --cflags` -fPIC

TEST_SRCS =				\
	codb1.c					\
	codb2.c				\
	test_txn_scanner.c 	\
	txn.c 				\
	populate_db.c	\
	txn_memtest.c   \
  leaktest.c

TEST_OBJS = $(patsubst %.c,%.o,$(TEST_SRCS))

TESTS =				\
	codb1       \
	codb2	\
	txn 				\
	test_txn_scanner    \
	populate_db	\
  leaktest

TEST_OUTS = $(patsubst %,%.out,$(TESTS))

all: test

test: $(TESTS)
	for a in $(TEST_OUTS); do echo -n $$a ": " ; tail -1 $$a ; done
	@echo Ran Tests.

../codb.o: FORCE
	make -C .. codb.o

leaktest: leaktest.o ../codb.o
	gcc -o $@ $^ $(LIBS)
	./$@

txn_memtest: txn_memtest.o ../codb.o
	gcc -o $@ $^ $(LIBS)
	./$@

codb1: codb1.o ../codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out ; 
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

codb2: codb2.o ../codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out ;
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

populate_db: populate_db.o ../codb.o
	gcc -o $@ $^ $(LIBS)
	./$@
	tar -zcvf PopulatedDB.tgz codb.oids codb

test_gc: test_gc.o ../codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out ; 
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

txn: txn.o ../codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out ; 
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test_txn_scanner: test_txn_scanner.o ../codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out  

clean:
	/bin/rm -f *.o core
	/bin/rm -f .depend
	/bin/rm -f $(TESTS) *.out
	/bin/rm -rf codb codb.oids

depend: $(TEST_SRCS)
	makedepend -f- -- $(CFLAGS) -- $^ > .depend

# special empty rule, always runs.
FORCE:

# dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
