# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../../dynamic.mk

INCLUDES += -I../include -I./txn
DEFS += -DCODB_INTERNAL
SRCS =	\
	odb_types.c \
	odb_attribs.c	\
	odb_helpers.c \
	codb2_glue.c \
	codb_find.c \
	codb_events.c \
	security.c \
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
	classconf_types.c \
	classconf_property.c \
	classconf_index.c \
	classconf_rule.c \
	classconf_matchtype.c \
	classconf_class.c \
	classconf.c \
	classconf_init.c \
	compare.c
OBJS = $(patsubst %.c,%.o,$(SRCS)) 

TEST_SRCS = \
	test/test_classconf_type.c \
	test/test_classconf.c \
	test/codb1.c \
	test/codb2.c \
	test/codb3.c \
	test/test_txn_scanner.c \
	test/txn.c \
	test/populate_db.c \
	test/txn_memtest.c \
	test/leaktest.c

TEST_OBJS = $(patsubst %.c,%.o,$(TEST_SRCS))
TEST_PROGS = $(patsubst %.c,%,$(TEST_SRCS))
TEST_OUTS = $(patsubst %.c,%.out,$(TEST_SRCS))

all: codb.o

impl/impl.o:
	$(MAKE) -C impl

codb.o: $(OBJS) impl/impl.o
	ld -r -o $@ $(OBJS) impl/impl.o

depend: .depend
.depend: $(SRCS) $(TESTSRCS)
	$(CC) -M $(CFLAGS) $^ > .depend

test: $(TEST_PROGS)

clean:
	$(MAKE) -C impl clean
	$(RM) -f $(OBJS) .depend codb.o
	$(RM) -f txn/odb_txn_lexer.c
	$(RM) -f $(TEST_OUTS) $(TEST_PROGS) 

txn/odb_txn_lexer.c: txn/odb_txn_lexer.re2c
	re2c $^ > $@

txn/odb_txn_lexer.o: txn/odb_txn_lexer.c
	$(CC) $(CFLAGS) -Wno-unused -c -o $@ $^

test/leaktest: test/leaktest.o codb.o
	$(CC) -o $@ $^ $(LIBS)
	./$@

test/txn_memtest: test/txn_memtest.o codb.o
	$(CC) -o $@ $^ $(LIBS)
	./$@

test/test_classconf_type: test/test_classconf_type.o codb.o
	$(CC) -o $@ $^ $(LIBS)

test/test_classconf_type.out: test/test_classconf_type FORCE
	./$^ &> $@ 
	@grep -i fail $@ || /bin/true
	@echo -n "===> $^: " ; tail -1 $@

test/test_classconf: test/test_classconf.o codb.o
	$(CC) -o $@ $^ $(LIBS)

test/test_classconf.out: test/test_classconf FORCE
	./test/test_classconf >& $@
	@grep -i fail $@ || /bin/true
	@echo -n "===> test_classconf: " ; tail -1 $@

test/codb1: test/codb1.o codb.o
	$(CC) -o $@ $^ $(LIBS)
	./$@ &> $@.out ; 
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test/codb2: test/codb2.o codb.o
	$(CC) -o $@ $^ $(LIBS)
	./$@ &> $@.out ; /bin/true;
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test/codb3: test/codb3.o codb.o
	$(CC) -o $@ $^ $(LIBS)
	./$@ &> $@.out ;
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test/populate_db: test/populate_db.o codb.o
	$(CC) -o $@ $^ $(LIBS)
	./$@
	tar -zcvf PopulatedDB.tgz codb.oids codb

test/txn: test/txn.o codb.o
	$(CC) -o $@ $^ $(LIBS)
	./$@ &> $@.out ; 
	@grep -i fail $@.out || /bin/true
	@echo -n "===> $@: " ; tail -1 $@.out

test/test_txn_scanner: test/test_txn_scanner.o codb.o
	$(CC) -o $@ $^ $(LIBS)
	./$@ &> $@.out  

FORCE:

# dependency info
ifeq (.depend,$(wildcard .depend))
include .depend
endif
