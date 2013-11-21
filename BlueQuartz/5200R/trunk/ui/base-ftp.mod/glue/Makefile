ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

all:

clean:

install:
	mkdir -p $(PREFIX)/usr/sausalito/bin
	install -m 0755 bin/addIdentLookups.pl $(PREFIX)/usr/sausalito/bin/addIdentLookups.pl
