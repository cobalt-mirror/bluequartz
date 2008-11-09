# $Id: Makefile 3 2003-07-17 15:19:15Z will $

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CC = gcc
INCLUDES = -I../include -I../../include -I$(CCEINCDIR) -Iimpl/include -Itxn 
DEFS = -DCODB_INTERNAL
LOCALDEBUG = -ggdb $(DEBUG)
CFLAGS = -O2 -Wall $(INCLUDES) $(DEFS) $(LOCALDEBUG) `glib-config --cflags` 
LIBDIRS = -L../../ -L$(CCELIBDIR)
LIBS = $(LIBDIRS) -lcce_common `glib-config --libs` -ldebug

.PHONY: test

all: depend codb.o

SRCS =		\
  odb_types.c 	\
  odb_attribs.c 	\
  odb_helpers.c   \
  codb2_glue.c    \
  codb_events.c	  \
  security.c	  \
  txn/odb_transaction.c \
  txn/odb_txn_commit.c \
  txn/odb_txn_createobj.c \
  txn/odb_txn_events.c \
  txn/odb_txn_inspect.c \
  txn/odb_txn_lists.c \
  txn/odb_txn_properties.c \
  txn/odb_txn_scalar.c \
  txn/odb_txn_serialize.c \
  txn/odb_txn_destroyobj.c \
  txn/odb_txn_lexer.c \
  txn/odb_txn_indexes.c \
  txn/odb_txn_new_inspect.c \
  txn/odb_txn_oids.c \
  impl/connect.c			\
  impl/file.c			\
  impl/list.c			\
  impl/misc.c			\
  impl/scalar.c		\
  classconf_types.c \
  classconf_property.c \
  classconf_class.c \
  classconf.c \
  classconf_init.c \
  compare.c 

MY_OBJS = $(patsubst %.c,%.o,$(SRCS)) 

codb.o: $(MY_OBJS) $(DIRS)
	ld -r -o $@ $(MY_OBJS)

TEST_SRCS =				\
	test/test_classconf_type.c \
	test/test_classconf.c \
	test/codb1.c					\
	test/codb2.c				\
	test/codb3.c				\
	test/test_txn_scanner.c 	\
	test/txn.c 				\
	test/populate_db.c	\
	test/txn_memtest.c   \
	test/leaktest.c

TEST_OBJS = $(patsubst %.c,%.o,$(TEST_SRCS))
TEST_PROGS = $(patsubst %.c,%,$(TEST_SRCS))
TEST_OUTS = $(patsubst %.c,%.out,$(TEST_SRCS))

txn/odb_txn_lexer.c: txn/odb_txn_lexer.re2c
	re2c $^ > $@

test: $(TEST_PROGS)

test/leaktest: test/leaktest.o codb.o
	gcc -o $@ $^ $(LIBS)
	./$@

test/txn_memtest: test/txn_memtest.o codb.o
	gcc -o $@ $^ $(LIBS)
	./$@

test/test_classconf_type: test/test_classconf_type.o codb.o
	gcc -o $@ $^ $(LIBS)

test/test_classconf_type.out: test/test_classconf_type FORCE
	./$^ &> $@ 
	@grep -i fail $@ || /bin/true
	@echo -n "===> $^: " ; tail -1 $@

test/test_classconf: test/test_classconf.o codb.o
	gcc -o $@ $^ $(LIBS)

test/test_classconf.out: test/test_classconf FORCE
	./test/test_classconf >& $@
	@grep -i fail $@ || /bin/true
	@echo -n "===> test_classconf: " ; tail -1 $@

test/codb1: test/codb1.o codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out ; 
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test/codb2: test/codb2.o codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out ; /bin/true;
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test/codb3: test/codb3.o codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out ;
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test/populate_db: test/populate_db.o codb.o
	gcc -o $@ $^ $(LIBS)
	./$@
	tar -zcvf PopulatedDB.tgz codb.oids codb

test/test_gc: test/test_gc.o codb.o
	@echo This test is now obsolete
	@/bin/fail
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out ; 
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test/txn: test/txn.o codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out ; 
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test/test_txn_scanner: test/test_txn_scanner.o codb.o
	gcc -o $@ $^ $(LIBS)
	./$@ &> $@.out  

test_sort2: test_sort2.c compare.o
	gcc -g -c test_sort2.c `glib-config --cflags`
	gcc -g -o $@ test_sort2.o compare.o

clean:
	/bin/rm -f `find . -type f -name core -print`
	/bin/rm -f `find . -type f -name \*.o -print`
	/bin/rm -f `find . -type f -name .depend -print`
	/bin/rm -f txn/odb_txn_lexer.c
	/bin/rm -f $(TEST_OUTS) $(TEST_PROGS) 
	

depend: .depend

.depend: $(SRCS) $(TESTSRCS)
	$(CC) -M $(CFLAGS) $^ > .depend
	
FORCE:


# dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
