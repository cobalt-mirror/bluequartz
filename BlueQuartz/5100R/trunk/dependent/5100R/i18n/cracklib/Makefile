#
# This is a header to make a dynamic PHP extension for crack.
#

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

all: phpize configure build 

phpize:
	cd crack ; /usr/bin/phpize

configure:
	cd crack ; ./configure

build:
	cd crack ; make

install:
	$(INSTALL) -o root -m 0755 crack/.libs/crack.so $(CCEPHPLIBDIR)
	$(INSTALL) -d -o root -m 0755 $(PREFIX)/usr/share/dict; \
	$(INSTALL) -o root dict/cracklib-words -m 0644 $(PREFIX)/usr/share/dict/; \
	$(INSTALL) -o root dict/pw_dict.hwm -m 0644 $(PREFIX)/usr/share/dict/; \
	$(INSTALL) -o root dict/pw_dict.pwd -m 0644 $(PREFIX)/usr/share/dict/; \
	$(INSTALL) -o root dict/pw_dict.pwi -m 0644 $(PREFIX)/usr/share/dict/; \

local_clean:
	cd crack ; make clean
	cd crack ; rm -R autom4te.cache build include modules
	cd crack ; rm -f acinclude.m4 aclocal.m4 config.guess config.h config.h.in config.log config.nice config.status config.sub configure 
	cd crack ; rm -f configure.in install-sh libtool ltmain.sh Makefile Makefile.fragments Makefile.global Makefile.objects missing
	cd crack ; rm -f mkinstalldirs .deps

.PHONY: all configure phpize build install
.SILENT: all

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/rules.mk
else
include $(CCEDIR)/devel/rules.mk
endif

