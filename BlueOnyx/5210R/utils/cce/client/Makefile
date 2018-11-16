# $Id: Makefile 379 2004-07-12 09:36:57Z anders $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
include ../dynamic.mk

CCEMODULE = cce-libcce
CCERPMSPEC = cce-libcce.spec

PERLSRCS = perl/CCE.pm

LOCALES=$(shell dir=locale; \
                if [ -d $$dir ]; then \
                        cd $$dir; tmp='-I CVS'; \
                        for i in $XLOCALEPAT; do \
                                tmp="$$tmp -I $$i"; \
                        done; \
                        locales=`ls $$tmp | egrep '^..$$|^.._'`; \
                        for dir in $$locales; do \
                                if [ -f $$dir/cce.po ]; then \
                                        list="$$dir $$list"; \
                                fi; \
                        done; \
                        echo $$list; \
                fi)

# these are only useful to non SLK builds
ifneq ($(SLK_BUILD), yes)
all: mod_locale
	make -C src all
	make -C php all

clean:
	make -C src clean
	make -C php clean
	rm -f *.spec
	rm -f locale/*/*.mo
endif

install: perl_inst install_locale install_httpdconf
	make -C src install
	make -C php install

test: 
	make -C src test
	make -C php test

install_httpdconf:
	mkdir -p $(PREFIX)/etc/httpd/conf.d
	install -o root -g root -m 644 httpd/blueonyx.conf $(PREFIX)/etc/httpd/conf.d

perl_inst: $(PERLSRCS)
	mkdir -p $(CCE_PERLDIR) || /bin/true
	for t in $^; do \
		install -o root -g root -m 755 $$t $(CCE_PERLDIR); \
	done

LOC_FILE=/usr/sausalito/devel/module.mk

ifeq ($(SLK_BUILD), yes)
#include /usr/sausalito/devel/module.mk

VENDOR=base
VENDORNAME=cobalt
SERVICE=cce

VERSION=1.0
RELEASE=1

BUILDLOCALE=yes
BUILDUI=no
BUILDGLUE=no
BUILDSRC=no

BUILDARCH=noarch

locale_rpm: rpm
else

mod_locale:
	@$(MAKE) -f $(LOC_FILE) mod_locale BUILDLOCALE=yes LOCALES="$(LOCALES)" \
		SERVICE="cce"

install_locale:
	@$(MAKE) -f $(LOC_FILE) install_locale BUILDLOCALE=yes LOCALES="$(LOCALES)" \
		SERVICE="cce" VENDOR="base"

endif

depend:
	# nothing here yet

