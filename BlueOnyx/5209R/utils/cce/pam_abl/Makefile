# Makefile
# $Id: Makefile

include ../dynamic.mk

TITLE = pam_abl
MODULESLIBDIR=$(shell if [ `uname -m` = "x86_64" ]; then  echo -n "lib64"; else  echo -n "lib"; fi)
PAMDIR=$(PREFIX)/$(MODULESLIBDIR)/security
CONFDIR=$(PREFIX)/etc/security
DBDIR=$(PREFIX)/var/lib/pam_abl
MODULE=pam_abl.so
BUILDER=build.sh

# targets
all: $(TITLE)

$(TITLE): $(BUILDER)
	./$(BUILDER)

install: all
	mkdir -p $(PAMDIR)
	install --mode=755 --strip cmake_build/$(MODULE) $(PAMDIR)
	install --mode=644 conf/pam_abl.conf $(CONFDIR)
	install -d --mode=755 $(DBDIR)
	mkdir -p $(PREFIX)/usr/bin
	$(INSTALL) -m 755 -o root -g root cmake_build/pam_abl $(PREFIX)/usr/bin/pam_abl
	$(INSTALL) -m 755 -o root -g root cmake_build/pam_abl_test $(PREFIX)/usr/bin/pam_abl_test
	mkdir -p $(PREFIX)/usr/sausalito/bin
	$(INSTALL) -m 755 -o root -g root cmake_build/pam_abl $(PREFIX)/usr/sausalito/bin/pam_abl_gui

clean:
	rm -Rf cmake_build
