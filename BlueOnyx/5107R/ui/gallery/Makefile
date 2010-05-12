ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

UIDIR = $(CCEDIR)/ui
STYLEDIR = $(UIDIR)/style
WEBDIR = $(UIDIR)/web

CCEBASE=/usr/sausalito
CCEBIN=$(CCEBASE)/bin
CCEDEVEL=$(CCEBASE)/devel
CCETMPL=$(CCEDEVEL)/templates

# for mod_rpmize
VENDOR = sausalito
VENDORNAME = 'Project BlueOnyx'
SERVICE = gallery
VERSION = 0.3.2
RELEASE = 0BQ12
UIMENUS = none
BUILDARCH=noarch
BUILDUI = no
BUILDGLUE=no
BUILDLOCALE=yes
#XLOCALEPAT =
LOCALES = $(shell dir=locale; \
                if [ -d $$dir ]; then \
                        cd $$dir; tmp='-I CVS'; \
                        for i in $(XLOCALEPAT); do \
                                tmp="$$tmp -I $$i"; \
                        done; \
                        locales=`ls $$tmp | egrep '^..$$|^.._'`; \
                        echo $$locales; \
                fi)

CCEMODULE = $(VENDOR)-$(SERVICE)-$(VERSION)
CCERPMSPEC = $(VENDOR)-$(SERVICE).spec


all: mod_locale

install: install_locale
	@install -d -o root -g root -m 755 $(UIDIR)
	@find style -type f -name "*.xml" -exec install -D -o root -g root -m 644 {} $(UIDIR)/{} \;
	@echo "chown style directory to $(USER_HTTPD) so it can write compiled styles to it..."
	@chown $(USER_HTTPD) $(STYLEDIR)
	@find libImage -type f ! -path "*/CVS/*" -exec install -D -o root -g root -m 644 {} $(WEBDIR)/{} \;
	@echo "installing locale-sensitive styles...";
	for i in `find locale -type f -name "*.xml"` ; do \
		echo "$$i" |sed "s/\// /g" | awk "{ print \$$1 \"/\" \$$2 \"/\" \$$3 \" \" \"$(STYLEDIR)/\" \$$3 \".\" \$$2 }" | xargs install -D -o root -g root -m 644 ; \
	done;

ifdef CCETOPDIR
 LOC_FILE=$(CCETOPDIR)/devel/module.mk
else
 LOC_FILE=$(CCEDIR)/devel/module.mk
endif

mod_locale:
	@$(MAKE) -f $(LOC_FILE) mod_locale BUILDLOCALE=yes LOCALES="$(LOCALES)"
 
install_locale:
	@$(MAKE) -f $(LOC_FILE) install_locale VENDOR="" BUILDLOCALE=yes LOCALES="$(LOCALES)"

clean:
	rm -rf rpms srpms $(CCERPMSPEC) $(CCERPMSPEC).new

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/package.mk
else
include $(CCEDIR)/devel/package.mk
endif


rpm: clean specfile
	@if [ x'$(CCEMODULE)' = x -o x'$(CCERPMSPEC)' = x ]; then \
		echo "CCEMODULE or CCERPMSPEC not specified."; \
		exit -1; \
	fi
	@echo "building rpm..."
	-rm -rf $(CCETMPDIR)/$(CCEMODULE)
	-mkdir -p $(CCETMPDIR)/$(CCEMODULE)
	tar --exclude '*CVS*' --exclude '.svn' --create --file - * | (cd $(CCETMPDIR)/$(CCEMODULE); tar xBf -)
	(cd $(CCETMPDIR); tar czBf $(PACKAGE_DIR)/SOURCES/$(CCEMODULE).tar.gz $(CCEMODULE))
	rm -rf $(CCETMPDIR)/$(CCEMODULE)
	CCEBASE=$(CCEBASE) VENDOR=$(VENDOR) VENDORNAME=$(VENDORNAME) \
	SERVICE=$(SERVICE) UIMENUS="$(UIMENUS)" VERSION=$(VERSION) \
	RELEASE=$(RELEASE) \
	BUILDUI=$(BUILDUI) BUILDGLUE=$(BUILDGLUE) BUILDARCH="$(BUILDARCH)" \
	LOCALE_NO_VENDOR=true NOCAPSTONES=true \
	BUILDLOCALE=$(BUILDLOCALE) LOCALES="$(LOCALES)" BUILDSRC=no \
	$(CCEBIN)/mod_rpmize $(CCETMPL)/rpmdefs.tmpl $(CCERPMSPEC) \
	  $(CCERPMSPEC).new &> /tmp/rpmize.log
	cp $(CCERPMSPEC).new $(PACKAGE_DIR)/SPECS/$(CCERPMSPEC)
	touch /tmp/time.stamp
	@/bin/rm -rf rpms srpms || /bin/true
	@mkdir rpms srpms || /bin/true
	$(RPMBUILD) -ba $(PACKAGE_DIR)/SPECS/$(CCERPMSPEC)
	find $(PACKAGE_DIR)/RPMS $(PACKAGE_DIR)/SRPMS -follow -type f -name \*.rpm -newer /tmp/time.stamp \
		-exec /bin/cp \{} rpms \; -print
	mv rpms/*.src.rpm srpms
	find rpms -type f -printf "RPM: %P\n" > packing_list

specfile: $(CCERPMSPEC).in
	sed -e 's/XX_USER_HTTPD_XX/$(USER_HTTPD)/' sausalito-gallery.spec.in > sausalito-gallery.spec
