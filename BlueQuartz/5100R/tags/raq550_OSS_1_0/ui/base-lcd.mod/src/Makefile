#
# Makefile for creating LED and LCD utilities
#

ROOT=$(PREFIX)
BINDIR = $(ROOT)/sbin
USRSBINDIR = $(ROOT)/usr/local/sbin
ETC = $(ROOT)/etc
RCD = $(ROOT)/etc/rc.d/init.d
PERLDIR = $(ROOT)/usr/sausalito/perl

INSTALL = install
CHMOD = chmod
CHOWN = chown

PERLMOD = LCD.pm

LIB = lib/liblcd.a
SRCS   = lcd-write.c lcd-swrite.c led-write.c lcdsleep.c lcd-yesno.c \
	 lcd-getip.c lcd-flash.c readbutton.c link.c linkstatus.c lcd-getpass.c
OBJS := $(SRCS:.c=.o)
PROG := $(OBJS:.o=) 
SCRIPT = lcd-menu lcdstop ruleflush lcdstart ipnmgw
MENU = lcd.d
INITS  = lcdsleep.init lcd-showip

LOCALEDIR = $(ROOT)/usr/share/locale
LOCALE := $(wildcard messages/panel.po.*)
LANGS := $(subst .,,$(suffix $(LOCALE)))

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

readbutton: readbutton.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

link: link.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

linkstatus: linkstatus.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

lcd-getpass: lcd-getpass.o $(LIB)
	$(CC) $(LDFLAGS) $< -o $@ $(LIBS)

clean:
	$(MAKE) -C lib clean
	-rm -f $(OBJS) $(PROG) *~ TAGS

install: install-all

install-all: install-utils install-scripts

install-scripts: 
	$(INSTALL) -d "$(ETC)/$(MENU)"
	tar -psc --exclude '*CVS*' $(MENU) | tar -x -C $(ETC)
	chown -R root.root $(ETC)/$(MENU)
	chmod g+s $(ETC)/$(MENU)
	chmod -R 500 $(ETC)/$(MENU)

install-utils: $(PROG) $(LOCALE)
	$(INSTALL) -d $(BINDIR)
	$(INSTALL) -s -m 500 $(PROG) $(BINDIR)
	$(INSTALL) -d $(USRSBINDIR)
	$(INSTALL) fppasswd.sh $(USRSBINDIR)
	$(INSTALL) -m 500 $(SCRIPT) $(BINDIR)
	$(INSTALL) -d $(RCD)
	$(INSTALL) $(INITS) $(RCD)
	$(INSTALL) -d $(PERLDIR)
	$(INSTALL) -m644 $(PERLMOD) $(PERLDIR)
	for A in $(LANGS); do \
	$(INSTALL) -m755 -d  $(LOCALEDIR)/$$A/LC_MESSAGES/; \
	cat messages/panel.po.$$A | \
	msgfmt --strict - -o $(LOCALEDIR)/$$A/LC_MESSAGES/panel; \
	done;

rpm: rpm-utils rpm-scripts

rpm-utils:
	make clean;\
	cd ..;tar -X src/.rpmignore -czvf panel-utils.tar.gz src &> /dev/null;\
	cp panel-utils.tar.gz /usr/src/redhat/SOURCES;\
        cp src/specs/panel-utils.spec /usr/src/redhat/SPECS;\
	rpm -ba /usr/src/redhat/SPECS/panel-utils.spec

rpm-scripts:
	cd ..; tar -X src/.rpmignore -czvf panel-scripts.tar.gz src/lcd.d src/fppasswd.sh;\
	cp panel-scripts.tar.gz /usr/src/redhat/SOURCES;\

	@if [ x"$(PRODUCT)" = x"alpine" ]; then \
		echo "Building Pt.Lobos (Alpine) style LCD scripts."; \
		echo `pwd`; \
		cp specs/panel-scripts-alpine.spec /usr/src/redhat/SPECS; \
		rpm -ba /usr/src/redhat/SPECS/panel-scripts-alpine.spec; \
	else if [ x"$(PRODUCT)" = x"bto" ]; then \
		echo "Building BTO style LCD scripts."; \
		cp specs/panel-scripts-bto.spec /usr/src/redhat/SPECS; \
		rpm -ba /usr/src/redhat/SPECS/panel-scripts-bto.spec; \
	else if [ x"$(PRODUCT)" = x"bigdaddy" ]; then \
		echo "Building Big Daddy style LCD scripts."; \
		cp specs/panel-scripts-bigdaddy.spec /usr/src/redhat/SPECS;\
		rpm -ba /usr/src/redhat/SPECS/panel-scripts-bigdaddy.spec;\
	else \
		echo "PRODUCT not specified, building Carmel style LCD scripts."; \
		cp specs/panel-scripts-carmel.spec /usr/src/redhat/SPECS;\
		rpm -ba /usr/src/redhat/SPECS/panel-scripts-carmel.spec; \
	fi; fi; fi
