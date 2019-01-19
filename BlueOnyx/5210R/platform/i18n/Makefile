ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CCEMODULE=sausalito-i18n
CCERPMSPEC=$(CCEMODULE).spec
DIRS=src perl cracklib php

V_MAJ=0
V_MIN=81
VERSION=$(V_MAJ).$(V_MIN).2
RELEASE=0BX05

.PHONY: $(CCERPMSPEC)

all: version.mk
	for dir in $(DIRS); do \
		make -C $$dir $(MAKECMDGOALS); \
	done

clean local_clean:
	for dir in $(DIRS); do \
		make -C $$dir $(MAKECMDGOALS); \
	done
	rm -f version.mk
	rm -rf rpms srpms

distclean:  
	make clean
	rm -f $(CCERPMSPEC)

install: all
	for dir in $(DIRS); do \
		make -C $$dir $(MAKECMDGOALS); \
	done

$(CCERPMSPEC): $(CCERPMSPEC).in Makefile
	sed -e 's/XX_VER_XX/$(VERSION)/' -e 's/XX_REL_XX/$(RELEASE)/' $< > $@

version.mk:
	rm -f version.mk
	echo V_MAJ=$(V_MAJ) >> version.mk
	echo V_MIN=$(V_MIN) >> version.mk
	echo VERSION=$(VERSION) >> version.mk

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/package.mk
else
include $(CCEDIR)/devel/package.mk
endif
