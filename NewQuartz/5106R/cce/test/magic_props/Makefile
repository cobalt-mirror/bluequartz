# $Id: Makefile,v 1.1.2.1 2002/03/01 01:37:13 thockin Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

TESTDIR=tmp
SERVER=../../server/cced

test:	install
	$(SERVER) -c $(TESTDIR) -nl -nt >server.out 2>&1 &
	perl ./magic_test $(TESTDIR)/cced.socket >client.out 2>&1
	sleep 1
	killall $(SERVER)
	cat server.out \
		| sed 's/^client \([0-9]*\):\[.*\]:/client \1:PID:/' \
		| grep -v 'starting up' \
		| grep -v "has admin rights" \
		>server.out.clean
	diff -u server.out.clean server.output
	diff -u client.out client.output

.PHONY: install
install:
	rm -rf $(TESTDIR)
	mkdir -p $(TESTDIR)/schemas
	cp *.schema $(TESTDIR)/schemas
	cp ../../server/basetypes.schema $(TESTDIR)/schemas
	mkdir -p $(TESTDIR)/conf
	mkdir -p $(TESTDIR)/handlers
	mkdir -p $(TESTDIR)/bin
	cat ../../pperl/pperld | sed '/^use Sauce/d' >$(TESTDIR)/bin/pperld
	chmod +x $(TESTDIR)/bin/pperld
	cp ../../pperl/pperl $(TESTDIR)/bin/
	
clean:
	rm -rf $(TESTDIR)
	rm -f server.out client.out server.out.clean

