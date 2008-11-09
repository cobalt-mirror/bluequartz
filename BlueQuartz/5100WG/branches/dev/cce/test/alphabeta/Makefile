# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

TESTDIR=tmp
SERVER=../../server/cced

test:	testinstall
	$(SERVER) -c $(TESTDIR) -nl -nt >server.out 2>&1 &
	perl ./alphabeta_test $(TESTDIR)/cced.socket >client.out 2>&1
	sleep 1
	killall $(SERVER)
	cat server.out | sed 's/^client \([0-9]*\):\[.*\]:/client \1:PID:/' | grep -v 'starting up' >server.out.clean
	diff -u server.out.clean server.output
	diff -u client.out client.output

.PHONY: testinstall
testinstall:
	rm -rf $(TESTDIR)
	mkdir -p $(TESTDIR)/schemas
	cp *.schema $(TESTDIR)/schemas
	cp ../../server/basetypes.schema $(TESTDIR)/schemas
	mkdir -p $(TESTDIR)/conf
	cp *.conf $(TESTDIR)/conf
	mkdir -p $(TESTDIR)/handlers
	cp *_handler $(TESTDIR)/handlers
	chmod +x $(TESTDIR)/handlers/*_handler
	mkdir -p $(TESTDIR)/bin
	cat ../../tools/pperl/pperld | sed '/^use Sauce/d' >$(TESTDIR)/bin/pperld
	chmod +x $(TESTDIR)/bin/pperld
	cp ../../tools/pperl/pperl $(TESTDIR)/bin/
	
clean:
	rm -rf $(TESTDIR)
	rm -f server.out client.out server.out.clean

