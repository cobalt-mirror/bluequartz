ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CCEMODULE = cce-libcce
CCERPMSPEC = cce-libcce.spec

PERLSRCS = perl/CCE.pm

LOCALES=$(shell dir=locale; \
                if [ -d $$dir ]; then \
                        cd $$dir; tmp='-I CVS'; \
                        for i in $(XLOCALEPAT); do \
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

ifneq ($(SLK_BUILD), yes)

all: include/cce mod_locale
	make -C src 
	make -C php

endif

depend: include/cce
	
include/cce:
	-ln -s . include/cce

ifneq ($(SLK_BUILD), yes)

clean:
	make -C src clean
	make -C php clean
	rm -f *.spec

endif

install: perl_inst install_locale install_headers
	make -C src install
	make -C php install

test: 
	make -C src test
	make -C php test

perl_inst: $(PERLSRCS)
	mkdir -p $(CCEPERLDIR) || /bin/true
	for t in $^; do \
		install -o root -g root -m 755 $$t $(CCEPERLDIR); \
	done

ifdef CCETOPDIR
LOC_FILE=$(CCETOPDIR)/devel/module.mk
else
LOC_FILE=$(CCEDIR)/devel/module.mk
endif

install_headers:
	mkdir -p $(CCEINCDIR)/cce/
	$(INSTALL) -o root -g root -m 644 include/*.h $(CCEINCDIR)/cce/

ifeq ($(SLK_BUILD), yes)
include /usr/sausalito/devel/module.mk

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
