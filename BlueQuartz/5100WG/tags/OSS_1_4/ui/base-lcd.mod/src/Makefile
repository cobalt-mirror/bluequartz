#
# Makefile for creating LED and LCD utilities
#

ROOT=$(PREFIX)
BINDIR = $(ROOT)/sbin
ETC = $(ROOT)/etc
RCD = $(ROOT)/etc/rc.d/init.d
PERLDIR = $(ROOT)/usr/sausalito/perl

INSTALL = install
CHMOD = chmod
CHOWN = chown

PERLMOD = LCD.pm

LIB = lib/liblcd.a
SRCS   = lcd-write.c lcd-swrite.c led-write.c lcdsleep.c lcd-yesno.c \
	 lcd-getip.c lcd-flash.c readbutton.c link.c linkstatus.c lcdstart.c \
	 lcd-getpass.c
OBJS := $(SRCS:.c=.o)
PROG := $(OBJS:.o=)
SCRIPT = lcd-menu lcdstop ruleflush
MENU = lcd.d
INITS  = lcdsleep.init lcd-showip

DEBUG  = -g -Wall
COPTS  = -O2
CFLAGS = $(DEBUG) $(COPTS) 
LIBS = -llcd
LDFLAGS = -L./lib

all::	library $(PROG)

library: 
	$(MAKE) -C lib

$(OBJS): lcdutils.h 

lcd-write: lcd-write.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

lcd-swrite: lcd-swrite.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

led-write: led-write.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

lcdsleep: lcdsleep.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

lcd-yesno: lcd-yesno.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

lcd-getip: lcd-getip.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

lcd-flash: lcd-flash.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

lcd-getpass: lcd-getpass.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

lcdstart: lcdstart.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

readbutton: readbutton.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

link: link.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

linkstatus: linkstatus.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

clean:
	$(MAKE) -C lib clean
	-rm -f $(OBJS) $(PROG) *~ TAGS

install: install-all

install-all: install-utils install-scripts

install-scripts: 
	$(INSTALL) -d $(ETC)
	cp -pr $(MENU) $(ETC)
	chown -R root.root $(ETC)/$(MENU)
	chmod g+s $(ETC)/$(MENU)
	chmod -R 500 $(ETC)/$(MENU)

install-utils: $(PROG)
	$(INSTALL) -d $(BINDIR)
	$(INSTALL) -s -m 500 $(PROG) $(BINDIR)
	$(INSTALL) -m 500 $(SCRIPT) $(BINDIR)
	$(INSTALL) -d $(RCD)
	$(INSTALL) $(INITS) $(RCD)
	$(INSTALL) -d $(PERLDIR)
	$(INSTALL) -m644 $(PERLMOD) $(PERLDIR)

rpm: rpm-utils rpm-scripts-carmel

rpm-utils:
	make clean;\
	cd ..;tar -X src/.rpmignore -czvf panel-utils.tar.gz src &> /dev/null;\
	cp panel-utils.tar.gz /usr/src/redhat/SOURCES;\
        cp src/specs/panel-utils.spec /usr/src/redhat/SPECS;\
	rpm -ba /usr/src/redhat/SPECS/panel-utils.spec

rpm-scripts-pacifica:
	cd ..;tar -X src/.rpmignore -czvf panel-scripts.tar.gz src/lcd.d &> /dev/null;\
	cp panel-scripts.tar.gz /usr/src/redhat/SOURCES;\
	cp src/specs/panel-scripts-pacifica.spec /usr/src/redhat/SPECS;\
	rpm -ba /usr/src/redhat/SPECS/panel-scripts-pacifica.spec

rpm-scripts-carmel:
	cd ..; tar -X src/.rpmignore -czvf panel-scripts.tar.gz src/lcd.d;\
	cp panel-scripts.tar.gz /usr/src/redhat/SOURCES;\
	cp src/specs/panel-scripts-carmel.spec /usr/src/redhat/SPECS;\
	rpm -ba /usr/src/redhat/SPECS/panel-scripts-carmel.spec

rpm-scripts-bto:
	cd ..; tar -X src/.rpmignore -czvf panel-scripts.tar.gz src/lcd.d;\
	cp panel-scripts.tar.gz /usr/src/redhat/SOURCES;\
	cp src/specs/panel-scripts-bto.spec /usr/src/redhat/SPECS;\
	rpm -ba /usr/src/redhat/SPECS/panel-scripts-bto.spec
