# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

TESTDIR=tmp
SERVER=../../server/cced

test:	install
	sudo $(SERVER) -c $(TESTDIR) -nl >server.out 2>&1 &
	perl ./security_test $(TESTDIR)/cced.socket >client.out 2>&1
	sudo killall $(SERVER)
	cat server.out | sed 's/^client \([0-9]*\):\[.*\]:/client \1:PID:/' | grep -v 'starting up' >server.out.clean
	diff -u server.out.clean server.output
	diff -u client.out client.output

.PHONE: install
install:
	sudo rm -rf $(TESTDIR)
	mkdir -p $(TESTDIR)/schemas
	cp *.schema $(TESTDIR)/schemas/
	cp ../../server/basetypes.schema $(TESTDIR)/schemas
	mkdir -p $(TESTDIR)/conf
	cp *.conf $(TESTDIR)/conf/
	mkdir -p $(TESTDIR)/handlers
	cp *_handler $(TESTDIR)/handlers/
	chmod +x $(TESTDIR)/handlers/*_handler
	mkdir -p $(TESTDIR)/bin
	cat ../../pperl/pperld | sed '/^use Sauce/d' >$(TESTDIR)/bin/pperld
	chmod +x $(TESTDIR)/bin/pperld
	cp ../../pperl/pperl $(TESTDIR)/bin/

clean:
	sudo rm -rf $(TESTDIR)
	rm -f server.out client.out server.out.clean

