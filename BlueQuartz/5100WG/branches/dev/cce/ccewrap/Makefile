#
# Created by Michael Waychison <mwaychison@cobalt.com> 2000/7/05
# $id $
#
TITLE = ccewrap
CONF = ccewrap.conf
OBJS = ccewrap.o ccewrap_conf.o ccewrap_program.o ccewrap_capability.o ../common/src/xml_parse.o 
SUIDINSTALL = 4750
INSTALL_BIN_PERMS = 755

# defines that should be later removed:
CC = gcc
INSTALL = install
INCLUDEDIR =  -I../client/include -I/usr/sausalito/include -I../include
LIBDIRS = -L../client/src -L/usr/sausalito/lib
LIBS = $(LIBDIRS) `glib-config --libs` -lcce
CFLAGS =  `glib-config --cflags` -ggdb -Wall $(INCLUDEDIR)

# targets
all: $(TITLE)

$(TITLE): $(OBJS)
	$(CC) $(LIBS) $(OBJS) -o $(TITLE)

install: all
	$(INSTALL) -m $(SUIDINSTALL) -o root -g httpd $(TITLE) $(PREFIX)/usr/sausalito/bin/$(TITLE)
	$(INSTALL) -m 600 -o root -g root $(CONF) $(PREFIX)/etc/$(CONF)

clean:
	rm -f $(TITLE) $(OBJS)

test:
	cd tests; ./test.pl
