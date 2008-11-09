# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.

TESTDIR=tmp
SERVER=../../server/cced
CLIENT=../../tools/cceclient

test:	testinstall
	$(SERVER) -nt -c $(TESTDIR) -nl >server.out 2>&1 &
	perl sorttest.pl tmp/cced.socket >client.out 2>&1
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

clean:
	rm -rf $(TESTDIR)
	rm -f server.out server.out.clean client.out

