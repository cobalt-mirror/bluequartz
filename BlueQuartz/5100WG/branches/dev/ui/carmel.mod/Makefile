# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME=base
SERVICE=carmel

VERSION=1.0.1
RELEASE=56

# add a buildarch if desired
BUILDARCH=noarch

# default locale for static pages
#DEFLOCALE=

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=no
BUILDLOCALE=yes
BUILDSRC=no

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp
MKDIR=mkdir -p

include /usr/sausalito/devel/module.mk

install:
	$(MKDIR) $(CCEDIR)/ui/web
	for locale in $(XLOCALEPAT); do \
		exclude="$$exclude -and -not -path *\.$$locale"; \
	done; \
	find web -type f -not -path "*/CVS*" $$exclude -exec install -D -o root -g root -m 644 {} $(CCEDIR)/ui/{} \;
	$(MKDIR) $(CCEDIR)/ui/web/libImage
	find libImage -type f -not -path "*/CVS*" $$exclude -exec install -D -o root -g root -m 644 {} $(CCEDIR)/ui/web/{} \;
	include="-false"; \
	for locale in $(LOCALES); do \
		include="$$include -or -path *\.$$locale"; \
	done; \
	FLIST=`find web/error -follow -type f $$include \
		2>/dev/null | grep -v CVS | grep -v '/\.'`; \
	for file in $$FLIST; do \
		isfallback=`echo $$file | grep '.$(DEFLOCALE)$$'`; \
		if [ x"$$isfallback" != x"" ]; then \
			name=`basename $$file .$(DEFLOCALE)`; \
			if [ ! -f $(CCEDIR)/ui/web/error/$$name ]; then \
			`ln -s $$name.$(DEFLOCALE) \
				$(CCEDIR)/ui/web/error/$$name`; \
			fi; \
		fi; \
	done;
