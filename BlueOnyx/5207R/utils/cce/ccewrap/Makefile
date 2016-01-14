# $Id: Makefile 278 2004-01-15 01:52:16Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

include ../dynamic.mk

TITLE = ccewrap
CONF = ccewrap.conf
OBJS = ccewrap.o ccewrap_conf.o ccewrap_program.o ccewrap_capability.o

INCLUDES +=  -I../client/include
LIBS += -L../client/src -lcce

# targets
all: $(TITLE)

$(TITLE): $(OBJS)
	$(CC) $(OBJS) $(LIBS) -o $(TITLE)

install: all
	$(INSTALL) -m 4750 -o root -g $(USER_HTTPD) $(TITLE) $(CCE_BINDIR)
	$(INSTALL) -m 600 -o root -g root $(CONF) $(PREFIX)/etc
	$(INSTALL) -d -m 700 -o root -g root $(PREFIX)/etc/ccewrap.d
	$(INSTALL) -m 755 -o root -g root cced_unstuck.sh $(CCE_BINDIR)
	$(INSTALL) -m 755 -o root -g root check_cce.pl $(CCE_BINDIR)
	mkdir -p $(PREFIX)/etc/sudoers.d
	$(INSTALL) -m 644 -o root -g root admserv $(PREFIX)/etc/sudoers.d/

clean:
	rm -f $(TITLE) $(OBJS)

test:
	cd tests; ./test.pl
