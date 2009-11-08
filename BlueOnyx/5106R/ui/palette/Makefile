ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif 
 
include $(CCETOPDIR)/devel/defines.mk

BASEDIR = $(CCEDIR)
UIDIR = $(BASEDIR)/ui

INSTALLDIRS = conf libPhp web menu

CCEMODULE = sausalito-palette
CCERPMSPEC = sausalito-palette.spec

VERSION=0.5.1
RELEASE=0BQ20

LOCALES=$(shell dir=locale; \
                if [ -d $$dir ]; then \
                        cd $$dir; tmp='-I svn'; \
                        for i in $(XLOCALEPAT); do \
                                tmp="$$tmp -I $$i"; \
                        done; \
                        locales=`ls $$tmp | egrep '^..$$|^.._'`; \
                        for dir in $$locales; do \
                                if [ -f $$dir/palette.po ]; then \
                                        list="$$dir $$list"; \
                                fi; \
                        done; \
                        echo $$list; \
                fi)

ifneq ($(SLK_BUILD), yes)

all: mod_locale

endif

install: install_locale
	install -d -o root -g root -m 755 $(UIDIR)
	find $(INSTALLDIRS) -type f -not -regex ".*svn.*" -exec \
		install -D -o root -g root -m 644 {} $(UIDIR)/{} \;
	find sbin -type f -not -regex ".*svn.*" -exec\
		install -D -o root -g root -m 755 {} $(BASEDIR)/{} \;
	cd ccewrap; find . -type f -not -regex ".*svn.*" -exec \
		install -D -o root -g root -m 644 {} $(CCEWRAPD)/{} \;
#	make -C locale/en install
#	make -C locale/ja install

clean:
	rm -rf rpms srpms 
	find locale/ -name *.mo | xargs rm -f 

ifneq ($(SLK_BUILD), yes)
ifdef CCETOPDIR
include $(CCETOPDIR)/devel/package.mk
else
include $(CCEDIR)/devel/package.mk
endif
endif


ifeq ($(SLK_BUILD), yes)
include hack_specific.mk
endif

ifdef CCETOPDIR
 LOC_FILE=$(CCETOPDIR)/devel/module.mk
else
 LOC_FILE=$(CCEDIR)/devel/module.mk
endif
 
mod_locale:
	@$(MAKE) -f $(LOC_FILE) mod_locale BUILDLOCALE=yes LOCALES="$(LOCALES)" SERVICE="palette"
 
install_locale:
	@$(MAKE) -f $(LOC_FILE) install_locale BUILDLOCALE=yes LOCALES="$(LOCALES)" SERVICE="palette"


