ifndef CCEBASE
CCEBASE = /usr/sausalito
endif

include $(CCEBASE)/devel/defines.mk

SCRIPTS=sbin/ftpaccess.pl

all:

clean:

install:
	mkdir -p $(PREFIX)/usr/sausalito/bin
	install -m 0755 bin/addIdentLookups.pl $(PREFIX)/usr/sausalito/bin/addIdentLookups.pl
	mkdir -p $(PREFIX)/usr/sausalito/sbin
	for file in $(SCRIPTS); do \
	        $(INSTALL) $(INSTALL_BINFLAGS) $$file $(CCESBINDIR); \
	done

