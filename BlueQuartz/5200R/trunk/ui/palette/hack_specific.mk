
VENDOR=base
VENDORNAME=cobalt
SERVICE=palette

VERSION=1.0
RELEASE=2

BUILDLOCALE=yes
BUILDUI=no
BUILDGLUE=no
BUILDSRC=no

BUILDARCH=noarch

include /usr/sausalito/devel/module.mk

rpm: clean mod_specfile hack_specfile
	touch $(TIMEFILE)
	/bin/tar czf $(PACKAGE_DIR)/SOURCES/base-palette-$(VERSION).tar.gz -C .. palette \
		--exclude '*CVS*' --exclude '*.spec'
	rpm -bb $(VENDOR)-$(SERVICE).spec &> /tmp/rpm.palette.log
	mkdir -p rpms
	find $(PACKAGE_DIR)/RPMS -type f -newer $(TIMEFILE) -exec cp {} rpms/ ';'

hack_specfile: mod_specfile
	mv $(VENDOR)-$(SERVICE).spec pre_hack.spec
	sed -e 's/%{Vendor}-\(.*.mo\)/\1/g;s/%{Vendor}-%{Service}.prop/%{Service}.prop/g;' \
	pre_hack.spec > $(VENDOR)-$(SERVICE).spec

