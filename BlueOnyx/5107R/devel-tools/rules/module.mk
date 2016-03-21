# $Id: module.mk 960 2006-10-11 15:30:06Z shibuya $

include /usr/sausalito/devel/defines.mk

#
# The code below relates only to authorised package developers - not used for anyone else
#

WHAM_ME=$(shell if [ -f /usr/sausalito/devel/wham.mk ]; then echo TRUE; fi)

ifeq ($(WHAM_ME),TRUE)
    include /usr/sausalito/devel/wham.mk
endif


MSGFMT=msgfmt
REENCODE=/usr/sausalito/bin/reencode.pl
PERL=/usr/bin/perl
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

CCEBIN=$(CCEBASE)/bin
CCEDEVEL=$(CCEBASE)/devel
CCETMPL=$(CCEDEVEL)/templates

LOCALEDIR=$(CCELOCALEDIR)

UIMENUS=menu lcd-menu console-menu
UISERVICES=web console
UILOCALES=web
UIEXTENSIONS=extensions
UILCD=lcd

#
## Chorizo-Defines:
#
CHORIZO_MENUS=chorizo/menu
CHORIZO_SERVICES=chorizo/web
CHORIZO_EXTENSIONS=chorizo/extensions

GLUEDIRS=schemas conf
GLUEBINDIRS=handlers rules

FTP_PUT=$(CCEBIN)/ftp_put
TIMEFILE:=/tmp/make.tmp
SUBSTVARS_CMD=$(CCEBIN)/makePackageVars

# bleah
LOCALES=$(shell if [ -d "locale/$(VENDORNAME)" ]; then \
			dir="locale/$(VENDORNAME)"; \
		else \
			dir=locale; \
		fi; \
		if [ -d $$dir ]; then \
			cd $$dir; tmp='-I .svn'; \
			for i in $(XLOCALEPAT); do \
	 			tmp="$$tmp -I $$i"; \
			done; \
			locales=`ls $$tmp | egrep '^..$$|^.._'`; \
			for dir in $$locales; do \
				if [ -f $$dir/$(SERVICE).po ]; then \
					list="$$dir $$list"; \
				fi; \
			done; \
			echo $$list; \
		fi)

all:: mod_ui mod_glue mod_perl mod_locale mod_src mod_capstone

install: all install_ui install_glue install_perl install_locale install_src install_capstone

.PHONY: mod_packing_list FORCE rpm package

FORCE:

clean:
	@echo "*******************************************************"
	@echo "** performing make clean ******************************"
	@echo "*******************************************************"
	-find . -type f \( -name \*~ -o -name \*.o -o \
			   -name \#\* -o -name \*.mo \) | xargs rm -f
	-rm -f $(VENDOR)-$(SERVICE).spec
	-if [ -f "src/$(VENDORNAME)/Makefile" ]; then \
		make -C "src/$(VENDORNAME)" clean; \
	fi
	-if [ -f src/Makefile ]; then \
		make -C src clean; \
	fi
	-if [ -f "glue/$(VENDORNAME)/Makefile" ]; then \
		make -C "glue/$(VENDORNAME)" clean; \
	fi
	-if [ -f glue/Makefile ]; then \
		make -C glue clean; \
	fi
	-if [ -f "ui/$(VENDORNAME)/Makefile" ]; then \
		make -C "ui/$(VENDORNAME)" clean; \
	fi
	-if [ -f ui/Makefile ]; then \
		make -C ui clean; \
	fi
	-rm -rf rpms srpms as_rpms as_srpms packing_list

src_rpms: FORCE
	touch $(TIMEFILE)
	if [ x"$(BUILDSRC)" = x"yes" ]; then \
		sleep 1; \
		if [ -d "src/$(VENDORNAME)" ]; then \
			dir="src/$(VENDORNAME)"; \
		else \
			dir=src; \
		fi; \
		if [ -f $$dir/Makefile ]; then \
			make -C $$dir rpm; \
		fi; \
		/bin/mkdir rpms || /bin/true ;\
		cp `find $(RPM_TOPDIR)/RPMS -follow -type f -newer $(TIMEFILE)` rpms ;\
		/bin/mkdir srpms || /bin/true ;\
		cp `find $(RPM_TOPDIR)/SRPMS -follow -type f -newer $(TIMEFILE)` srpms ;\
	fi
	-@/bin/rm -f $(TIMEFILE)

rpm: clean src_rpms mod_packing_list mod_specfile FORCE
	touch $(TIMEFILE)
	sleep 1
	#
	# now bundle up the rpm
	-rm -rf $(TMPDIR)/$(VENDOR)-$(SERVICE)-$(VERSION)
	-mkdir -p $(TMPDIR)/$(VENDOR)-$(SERVICE)-$(VERSION)
	tar --exclude '*CVS*' --exclude '*src*' --exclude '.svn' $(EXTRAEXCLUDES) -cBf - . | \
		(cd $(TMPDIR)/$(VENDOR)-$(SERVICE)-$(VERSION); \
		tar xBf -)
	(cd $(TMPDIR); tar czf \
	$(RPM_TOPDIR)/SOURCES/$(VENDOR)-$(SERVICE)-$(VERSION).tar.gz \
	$(VENDOR)-$(SERVICE)-$(VERSION))		
	-rm -rf $(TMPDIR)/$(VENDOR)-$(SERVICE)-$(VERSION)
ifeq ($(SLEEP), yes)
	sleep 10 # because NFS has slow updates, and some lame-brain at
		# red hat decided that it would be a good idea to
		# explicitly exclude non-causal events.  Foo.
endif
	$(RPMBUILD) -ta $(RPM_TOPDIR)/SOURCES/$(VENDOR)-$(SERVICE)-$(VERSION).tar.gz &> /tmp/rpm.$(PRODUCT)_$(SERVICE).log
	-@mkdir as_rpms || /bin/true
	cp `find $(RPM_TOPDIR)/RPMS -follow -type f -newer $(TIMEFILE)` as_rpms
	-@mkdir as_srpms || /bin/true
	cp `find $(RPM_TOPDIR)/SRPMS -follow -type f -newer $(TIMEFILE)` as_srpms
	if [ `ls rpms/* |wc -l` -gt 0 ]; then   cp rpms/*  as_rpms; fi 
	if [ `ls srpms/*|wc -l` -gt 0 ]; then  cp srpms/* as_srpms; fi
	-@/bin/rm -f $(TIMEFILE)

mod_specfile:
	# we build up our locale list here as well.
	if [ -f templates/rpmdefs.tmpl ]; then \
		rpmdefs=templates/rpmdefs.tmpl; \
	else \
		rpmdefs=$(CCETMPL)/rpmdefs.tmpl; \
	fi; \
	if [ -f templates/spec.tmpl ]; then \
		spec=templates/spec.tmpl; \
	else \
		spec=$(CCETMPL)/spec.tmpl; \
	fi; \
	CCEBASE=$(CCEBASE) VENDOR=$(VENDOR) VENDORNAME=$(VENDORNAME) \
	SERVICE=$(SERVICE) LCDDIR=$(LCDDIR) \
	UIDIRS="$(UIMENUS) $(UISERVICES) $(UIEXTENSIONS) $(UILCD) $(CHORIZO_MENUS) $(CHORIZO_SERVICES) $(CHORIZO_EXTENSIONS)" \
	VERSION=$(VERSION) RELEASE=$(RELEASE) LOCALES="$(LOCALES)" \
	BUILDUI=$(BUILDUI) BUILDGLUE=$(BUILDGLUE) REQUIRES="$(REQUIRES) " \
	BUILDLOCALE=$(BUILDLOCALE) BUILDARCH=$(BUILDARCH) \
	PROVIDES="$(PROVIDES)" DEFLOCALE=$(DEFLOCALE) CCEWEB=$(CCEWEB) \
	LICENSE=$(LICENSE) REQUIRES_GLUE="${REQUIRES_GLUE}" \
	REQUIRES_UI="${REQUIRES_UI}" \
	$(CCEBIN)/mod_rpmize $$rpmdefs $$spec \
	  $(VENDOR)-$(SERVICE).spec &> /tmp/rpmize.log
	/usr/bin/perl -pi -e "s/^Requires:\ +\n//" $(VENDOR)-$(SERVICE).spec  

# don't upload srpms, because we don't release these and the source is in cvs
# this is to keep glazed from getting filled up quite as quickly
rpm_upload:
	if [ -d rpms ] ; then \
		(cd rpms && $(FTP_PUT) -d $(RPM_DIR) * ) ; \
	fi
	#if [ -d srpms ] ; then \
	#	(cd srpms && $(FTP_PUT) -d $(SRPM_DIR) * ) ; \
	#fi
	if [ -d as_rpms ] ; then \
		(cd as_rpms && $(FTP_PUT) -d $(RPM_DIR) * ) ; \
	fi
	#if [ -d as_srpms ] ; then \
	#	(cd as_srpms && $(FTP_PUT) -d $(SRPM_DIR) * ) ; \
	#fi

mod_glue:
	-@if [ x"$(BUILDGLUE)" = x"yes" ]; then \
		echo "building glue..."; \
		if [ -d "glue/$(VENDORNAME)" ]; then \
			dir="glue/$(VENDORNAME)"; \
		else \
			dir=glue; \
		fi; \
		if [ -f $$dir/Makefile ]; then \
			make -C $$dir; \
		fi; \
	fi

mod_perl:
	-@echo "building perl..."
	-@if [ -f perl/Makefile ]; then \
		make -C perl; \
	elif [ -f perl/Makefile.PL ]; then \
		cd perl; $(PERL) Makefile.PL; \
		make -C perl; \
	fi

mod_ui:
	@if ([ x"$(BUILDUI)" = x"yes" ] || [ x"$(BUILDUI)" = x"old" ] || [ x"$(BUILDUI)" = x"new" ] ) ; then \
		echo "building ui..."; \
		if [ -d "ui/$(VENDORNAME)" ]; then \
			dir="ui/$(VENDORNAME)"; \
		else \
			dir=ui; \
		fi; \
		if [ -f $$dir/Makefile ]; then \
			make -C $$dir; \
		fi; \
	fi

mod_src:
	@if [ x"$(BUILDSRC)" = x"yes" ]; then \
		echo "building src..."; \
		if [ -d "src/$(VENDORNAME)" ]; then \
			dir="src/$(VENDORNAME)"; \
		else \
			dir=src; \
		fi; \
		if [ -f $$dir/Makefile ]; then \
			make -C $$dir; \
		fi; \
	fi

mod_locale:
	@if [ x"$(BUILDLOCALE)" = x"yes" ]; then \
	for locale in $(LOCALES); do \
		if [ -d "locale/$(VENDORNAME)" ]; then \
			dir="locale/$(VENDORNAME)/$$locale"; \
		else \
			dir=locale/$$locale; \
		fi; \
		for file in $$dir/*.po; do \
			file=$$dir/`basename $$file .po`; \
			echo "building $$file.mo..."; \
			$(MSGFMT) $$file.po -o $$file.mo || exit 1; \
		done; \
	done; \
	fi

mod_packing_list: FORCE
	echo "building packing list..."; \
	if [ -n "$(PACKINGLIST_SUFFIX)" ]; then \
		if [ -f "packing_list$(PACKINGLIST_SUFFIX)" ]; then \
			cp -f packing_list$(PACKINGLIST_SUFFIX) packing_list; \
		fi; \
	fi
	foundrpms=; \
	if [ x"$(BUILDSRC)" = x"yes" ]; then \
		foundrpms=`/usr/sausalito/bin/rpmsorter.sh rpms`; \
	fi; \
	rm -f packing_list.foo; \
	if [ ! -f packing_list ]; then touch packing_list packing_list.foo; fi; \
	cat packing_list | while read; do \
		case $$REPLY in \
			*\[AUTORPMS\]*) \
				echo $$REPLY >> packing_list.foo; \
				for foundrpm in $$foundrpms; do \
					echo RPM: $$foundrpm >> packing_list.foo; \
				done; \
				break; \
				;; \
			*) \
				echo $$REPLY >> packing_list.foo; \
				;; \
		esac; \
	done; \
	if [ x"`grep AUTORPMS packing_list.foo`" = x"" ]; then \
		echo "# [AUTORPMS] DO NOT REMOVE THIS LINE OR EDIT ANYTHING BELOW IT, BY ORDER OF THE BUILD PEOPLE" >> packing_list.foo; \
		for foundrpm in $$foundrpms; do \
			echo RPM: $$foundrpm >> packing_list.foo; \
		done; \
	fi
	mv packing_list.foo packing_list
# This is unnecessary since make_release_spec does this itself
#		if [ x"$(BUILDLOCALE)" = x"yes" ]; then \
#			for locale in $(LOCALES); do \
#			echo "RPM: $(VENDOR)-$(SERVICE)-locale-$$locale" >> packing_list; \
#			done; \
#		fi; \
#		if [ x"$(BUILDUI)" = x"yes" ]; then \
#			echo "RPM: $(VENDOR)-$(SERVICE)-ui" >> packing_list; \
#		fi; \
#		if [ x"$(BUILDGLUE)" = x"yes" ]; then \
#			echo "RPM: $(VENDOR)-$(SERVICE)-glue" >> packing_list; \
#		fi; \
#		echo "RPM: $(VENDOR)-$(SERVICE)-capstone" >> packing_list; \

mod_constructor:
	-@if [ -d "constructor/$(VENDORNAME)" ]; then \
		dir="constructor/$(VENDORNAME)"; \
	else \
		dir="constructor"; \
	fi; \
	if [ -f "$$dir/Makefile" ]; then \
		make -C $$dir; \
	fi

mod_destructor:
	-@if [ -d "destructor/$(VENDORNAME)" ]; then \
		dir="destructor/$(VENDORNAME)"; \
	else \
		dir="destructor"; \
	fi; \
	if [ -f "$$dir/Makefile" ]; then \
		make -C $$dir; \
	fi

mod_capstone: mod_constructor mod_destructor

install_ui:
	if ( [ x"$(BUILDUI)" = x"yes" ] || [ x"$(BUILDUI)" = x"old" ] || [ x"$(BUILDUI)" = x"new" ] ) ; then \
		echo "BUILDUI is YES or OLD or NEW"; \
		if [ -d "ui/$(VENDORNAME)" ];then\
			dir="ui/$(VENDORNAME)"; \
		else \
			dir=ui; \
		fi; \
		if ( [ x"$(BUILDUI)" = x"yes" ] || [ x"$(BUILDUI)" = x"old" ] ) ; then \
			echo "BUILDUI is YES or OLD"; \
			echo "installing UIMENUS $$dir..."; \
			for item in $(UIMENUS); do \
				FLIST=`find $$dir/$$item -follow -type f 2>/dev/null \
					| grep -v CVS | grep -v '/\.' | grep -v '/\.svn' `; \
				if [ -n "$$FLIST" ]; then \
				    echo "  -- installing $$item" ; \
				    mkdir -p $(CCEDIR)/ui/$$item/$(VENDOR)/$(SERVICE); \
				    $(INSTALL_OTH) $$FLIST \
					$(CCEDIR)/ui/$$item/$(VENDOR)/$(SERVICE); \
				fi; \
			done; \
		fi; \
		if ( [ x"$(BUILDUI)" = x"yes" ] || [ x"$(BUILDUI)" = x"new" ] ) ; then \
			echo "BUILDUI is YES or NEW"; \
			echo "installing CHORIZO_MENUS $$dir..."; \
			for item in $(CHORIZO_MENUS); do \
				FLIST=`find $$dir/$$item -follow -type f 2>/dev/null \
					| grep -v CVS | grep -v '/\.' | grep -v '/\.svn' `; \
				if [ -n "$$FLIST" ]; then \
				    echo "  -- installing $$item" ; \
				    mkdir -p $(CHORIZOMENU)/$(VENDOR)/$(SERVICE); \
				    echo "  -- CHORIZO Menu: installing SERVICE: $(SERVICE) to $(CHORIZOMENU)/$(VENDOR)/$(SERVICE)" ; \
				    $(INSTALL_OTH) $$FLIST $(CHORIZOMENU)/$(VENDOR)/$(SERVICE); \
				fi; \
			done; \
			echo "installing $$dir..."; \
			for item in $(CHORIZO_SERVICES); do \
				echo "  -- CHORIZO VENDOR: Using Vendor: $(VENDOR)" ; \
			    mkdir -p $(CHORIZOWEB)/$(VENDOR)/$(SERVICE)/config; \
			    mkdir -p $(CHORIZOWEB)/$(VENDOR)/$(SERVICE)/controllers; \
			    mkdir -p $(CHORIZOWEB)/$(VENDOR)/$(SERVICE)/models; \
			    mkdir -p $(CHORIZOWEB)/$(VENDOR)/$(SERVICE)/views; \
				FLIST=`find $$dir/$$item/config -follow -type f 2>/dev/null \
					| grep -v CVS | grep -v '/\.' | grep -v '/\.svn' `; \
				if [ -n "$$FLIST" ]; then \
				    echo "  -- installing $$item" ; \
				    echo "  -- FILELIST: $$FLIST" ; \
				    $(INSTALL_OTH) $$FLIST $(CHORIZOWEB)/$(VENDOR)/$(SERVICE)/config; \
				fi; \
				FLIST=`find $$dir/$$item/controllers -follow -type f 2>/dev/null \
					| grep -v CVS | grep -v '/\.' | grep -v '/\.svn' `; \
				if [ -n "$$FLIST" ]; then \
				    echo "  -- installing $$item" ; \
				    echo "  -- FILELIST: $$FLIST" ; \
				    $(INSTALL_OTH) $$FLIST $(CHORIZOWEB)/$(VENDOR)/$(SERVICE)/controllers; \
				fi; \
				FLIST=`find $$dir/$$item/models -follow -type f 2>/dev/null \
					| grep -v CVS | grep -v '/\.' | grep -v '/\.svn' `; \
				if [ -n "$$FLIST" ]; then \
				    echo "  -- installing $$item" ; \
				    echo "  -- FILELIST: $$FLIST" ; \
				    $(INSTALL_OTH) $$FLIST $(CHORIZOWEB)/$(VENDOR)/$(SERVICE)/models; \
				fi; \
				FLIST=`find $$dir/$$item/views -follow -type f 2>/dev/null \
					| grep -v CVS | grep -v '/\.' | grep -v '/\.svn' `; \
				if [ -n "$$FLIST" ]; then \
				    echo "  -- installing $$item" ; \
				    echo "  -- FILELIST: $$FLIST" ; \
				    $(INSTALL_OTH) $$FLIST $(CHORIZOWEB)/$(VENDOR)/$(SERVICE)/views; \
				fi; \
			done; \
			for item in $(CHORIZO_EXTENSIONS); do \
				if [ -d "$$dir/$$item" ]; then \
					echo "  -- installing CHORIZO_EXTENSIONS $$item"; \
					FLIST=`find $$dir/$$item -type f -not -path "*/.svn*" -printf "%P "`; \
					for file in $$FLIST; do \
						domain=`echo $$file | gawk -F . '{ printf "%s.%s", $$(NF-1), $$(NF); }'`; \
						install_name=`echo $$file | sed -e 's/\.[[:alnum:]]\+\.[[:alnum:]]\+$$//'`; \
						echo "  -- CHORIZO-EXTENSION: item: $$item - domain: $$domain - name: $$install_name"; \
						if [ x"$$domain" != x"$$install_name" ]; then \
							mkdir -p $(CHORIZOEXT)/$$domain/; \
							$(INSTALL_OTH) $$dir/$$item/$$file $(CHORIZOEXT)/$$domain/$$install_name; \
						fi; \
					done; \
				fi; \
			done; \
		fi; \
		exclude=""; \
		if ( [ x"$(BUILDUI)" = x"yes" ] || [ x"$(BUILDUI)" = x"old" ] ) ; then \
			echo "BUILDUI is YES or OLD"; \
			for locale in $(LOCALES); do \
				exclude="$$exclude -and -not -path *\.$$locale"; \
			done; \
			for item in $(UISERVICES); do \
				if [ -d "$$dir/$$item" ]; then \
					echo "  -- installing UISERVICES $$item" ; \
					`cd $$dir/$$item; find -follow -type d $$exclude \
						-not -path "*/.*" -not -path "*/.svn*" \
						-exec mkdir -p $(CCEDIR)/ui/$$item/$(VENDOR)/$(SERVICE)/{} \;`; \
					`cd $$dir/$$item; find -follow -type f $$exclude \
						-not -path "*/.*" -not -path "*/.svn*" \
						-exec $(INSTALL_OTH) {} $(CCEDIR)/ui/$$item/$(VENDOR)/$(SERVICE)/{} \;`; \
				fi; \
			done; \
			for item in $(UIEXTENSIONS); do \
				if [ -d "$$dir/$$item" ]; then \
					echo "  -- installing UIEXTENSIONS $$item"; \
					FLIST=`find $$dir/$$item -type f -not -path "*/.svn*" -printf "%P "`; \
					for file in $$FLIST; do \
						domain=`echo $$file | gawk -F . '{ printf "%s.%s", $$(NF-1), $$(NF); }'`; \
						install_name=`echo $$file | sed -e 's/\.[[:alnum:]]\+\.[[:alnum:]]\+$$//'`; \
						if [ x"$$domain" != x"$$install_name" ]; then \
							mkdir -p $(CCEDIR)/ui/$$item/$$domain/; \
							$(INSTALL_OTH) $$dir/$$item/$$file $(CCEDIR)/ui/$$item/$$domain/$$install_name; \
						fi; \
					done; \
				fi; \
			done; \
			for item in $(UILCD); do \
				if [ -d "$$dir/$$item" ]; then \
					echo "  -- installing UILCD $$item"; \
					FLIST=`find $$dir/$$item -type f -not -path "*/.svn*" \
						-not -path "*/.*" -printf "%P "`; \
					for file in $$FLIST; do \
						dirname=`dirname $$file`; \
						if [ "$$dirname" != "" ]; then \
							mkdir -m 0500 -p $(LCDINSTALLDIR)/$$dirname; \
						fi; \
						$(INSTALL) $(INSTALL_SBINFLAGS) $$dir/$$item/$$file \
							$(LCDINSTALLDIR)/$$dirname; \
					done; \
				fi; \
			done; \
			if [ -f $$dir/Makefile ]; then \
				PREFIX=$(PREFIX) make -C $$dir install; \
			fi; \
		fi; \
	fi


install_locale: mod_locale
	-@if [ x"$(BUILDLOCALE)" = x"yes" ]; then \
		if [ x"$(VENDOR)" = x"" ]; then \
			file_name=; \
		else \
			file_name=$(VENDOR)-; \
		fi; \
		for locale in $(LOCALES); do \
			if [ -d "locale/$(VENDORNAME)" ]; then \
				dir="locale/$(VENDORNAME)"; \
			else \
				dir=locale; \
			fi; \
			mkdir -p $(LOCALEDIR)/$$locale/LC_MESSAGES; \
			for mo_file in $$dir/$$locale/*.mo; do \
				basename=`basename $$mo_file`; \
				echo "installing $$dir/$$locale/$$basename..."; \
				$(INSTALL_OTH) $$mo_file \
					$(LOCALEDIR)/$$locale/LC_MESSAGES/$$file_name$$basename; \
			done; \
			if [ -f $$dir/$$locale/$(SERVICE).prop ]; then \
				cp $$dir/$$locale/$(SERVICE).prop $(LOCALEDIR)/$$locale/$${file_name}$(SERVICE).prop; \
			fi; \
		done; \
		if [ -d "ui/$(VENDORNAME)" ]; then \
			dir="ui/$(VENDORNAME)"; \
		else \
			dir=ui; \
		fi; \
		include="-false"; \
		for locale in $(LOCALES); do \
			include="$$include -or -path *\.$$locale"; \
		done; \
		for item in $(UILOCALES); do \
			FLIST=`find $$dir/$$item -follow -type f $$include \
				2>/dev/null | grep -v CVS | grep -v '/\.' | grep -v '/\.svn'`; \
echo $$FLIST; \
			if [ -n "$$FLIST" ]; then \
			    echo "  -- installing $$item" ; \
			    mkdir -p $(CCEDIR)/ui/$$item/$(VENDOR)/$(SERVICE); \
			    $(INSTALL_OTH) $$FLIST \
				$(CCEDIR)/ui/$$item/$(VENDOR)/$(SERVICE); \
			    for file in $$FLIST; do \
				isfallback=`echo $$file | grep '.$(DEFLOCALE)$$'`; \
				if [ x"$$isfallback" != x"" ]; then \
				    name=`basename $$file .$(DEFLOCALE)`; \
				    if [ ! -f $(CCEDIR)/ui/$$item/$(VENDOR)/$(SERVICE)/$$name ]; then \
					`ln -s $$name.$(DEFLOCALE) \
					    $(CCEDIR)/ui/$$item/$(VENDOR)/$(SERVICE)/$$name`; \
				    fi; \
				fi; \
			    done; \
			fi; \
		done; \
	fi

update_version:

install_glue: 
	-@if [ x"$(BUILDGLUE)" = x"yes" ]; then \
		if [ -d "glue/$(VENDORNAME)" ]; then \
			dir="glue/$(VENDORNAME)"; \
		else \
			dir=glue; \
		fi; \
		if [ -e glue/schema ] && [ ! -e glue/schemas ]; then \
			ln -s schema glue/schemas ; \
		fi; \
		echo "installing glue..."; \
		for name in $(GLUEDIRS); do \
			FLIST=`find $$dir/$$name -follow -type f 2>/dev/null \
				| grep -v CVS | grep -v '/\.' | grep -v '/\.svn'`; \
			if [ -n "$$FLIST" ]; then \
			    echo "  -- installing $$name" ; \
			    mkdir -p $(CCEDIR)/$$name/$(VENDOR)/$(SERVICE); \
			find $$dir/$$name -follow \( -name CVS -o -name '.svn' \) -prune -or \
				-type f -exec $(INSTALL_OTH) \{} \
				$(CCEDIR)/$$name/$(VENDOR)/$(SERVICE) \; ; \
			fi; \
		done; \
		FLIST=`find $$dir/ccewrap -follow -type f 2>/dev/null \
			| grep -v CVS | grep -v '/\.'`; \
		if [ -n "$$FLIST" ] ; then \
			echo "  -- installing ccewrap" ; \
			mkdir -p $(CCEWRAPD)/$(VENDOR)/$(SERVICE) ; \
			find $$dir/ccewrap -follow \( -name CVS -o -name '\.svn' \) -prune -or \
				-type f -exec $(INSTALL_OTH) \{} \
				$(CCEWRAPD)/$(VENDOR)/$(SERVICE) \; ; \
		fi; \
		for name in $(GLUEBINDIRS); do \
			FLIST=`find $$dir/$$name -follow -type f 2>/dev/null \
				| grep -v CVS | grep -v '/\.'`; \
			if [ -n "$$FLIST" ]; then \
			    echo "  -- installing $$name" ; \
			    mkdir -p $(CCEDIR)/$$name/$(VENDOR)/$(SERVICE); \
			    find $$dir/$$name \( -name CVS -o -name '\.svn' \) -prune -or -type f \
			    	-exec $(INSTALL_BIN) \{} \
			   	 $(CCEDIR)/$$name/$(VENDOR)/$(SERVICE) \; ; \
			fi; \
		done; \
		if [ -f $$dir/Makefile ]; then \
		      	echo "  -- running local Makefile" ; \
			PREFIX=$(PREFIX) make -C $$dir install; \
		fi; \
	fi

install_perl:
	-@if [ -d perl ]; then \
		if [ -d "perl/$(VENDORNAME)" ]; then \
			dir="perl/$(VENDORNAME)"; \
		else \
			dir=perl; \
		fi; \
		echo "installing perl..."; \
		if [ -f $$dir/Makefile ]; then \
			PREFIX=$(PREFIX) make -C $$dir install; \
		else \
			vendor_dir=`echo -n $(VENDOR) | $(PERL) -e '$$foo = <STDIN>; print ucfirst($$foo);'`; \
			mkdir -p $(CCEDIR)/perl/$$vendor_dir; \
			FLIST=`find $$dir -type f -not -path '*.svn*' \
				-not -name '\.*'`; \
			for file in $$FLIST; do \
				sans_perl=`echo $$file | sed -e 's/^perl\///'`; \
				dest=$(CCEDIR)/perl/$$vendor_dir/$$sans_perl; \
				$(INSTALL_OTH) -D $$file $$dest; \
			done; \
		fi; \
	fi

install_src:
	-@if [ x"$(BUILDSRC)" = x"yes" ]; then \
		if [ -d "src/$(VENDORNAME)" ]; then \
			dir="src/$(VENDORNAME)"; \
		else \
			dir=src; \
		fi; \
		echo "installing src..."; \
		if [ -f $$dir/Makefile ]; then \
			PREFIX=$(PREFIX) make -C $$dir install; \
		fi; \
	fi

install_capstone:
	-@if [ -f packing_list ]; then \
		mkdir -p $(CCEDIR)/capstone; \
		echo "installing capstone..."; \
		$(INSTALL_OTH) packing_list \
			$(CCEDIR)/capstone/$(VENDOR)-$(SERVICE).cap ;\
	fi

	-@FLIST=`find constructor -type f 2>/dev/null | grep -v CVS | grep -v '/\.' | grep -v '/\.svn'`; \
	if [ -n "$$FLIST" ]; then \
		mkdir -p $(CCEDIR)/constructor/$(VENDOR)/$(SERVICE); \
		echo "installing constructor..."; \
		$(INSTALL_BIN) $$FLIST \
			$(CCEDIR)/constructor/$(VENDOR)/$(SERVICE); \
	fi 

	-@FLIST=`find destructor -type f 2>/dev/null | grep -v CVS | grep -v '/\.' | grep -v '/\.svn'`; \
	if [ -n "$$FLIST" ]; then \
		mkdir -p $(CCEDIR)/destructor/$(VENDOR)/$(SERVICE); \
		echo "installing constructor..."; \
		$(INSTALL_BIN) $$FLIST \
			$(CCEDIR)/destructor/$(VENDOR)/$(SERVICE); \
	fi

pkg: install rpm
	-@echo "making package..."; \
	$(CCEBIN)/makePkg -v "$(VENDOR)" -s "$(SERVICE)" -n "$(VERSION)" \
		-r "$(RELEASE)" -a "$(BUILDARCH)" -l "$(LOCALES)" ;

package: 
# Variables needed in Makefile

# RPMSDIRS=directories that hold the rpms needed
# SRPMSDIRS=directories that hold the srpms needed
# Takes the following


# moduledir/
# 	pkginfo/		(1)
# 		locale/
# 			lang/
# 				blah.pl
# 		splash/
# 			pre/
# 			post/
# 			...

# 	scripts/		(2)
# 		pre/
# 		post/
# 		...
# 	as_rpms
# 	as_srpms
# 	packing_list (contains list of rpms needed for this pkg) (RPMS)
# 	pkgdefs.tmpl (i think, this contains partially filled out description of a package)

# From this we create
# package_results/

# 	package.tar.gz/
# 		packing_list	( 3 filled out with RPMS, SRPMS) (4)
# 		pkginfo/	(1)
# 			locale/
# 			splash/
# 		subpackage.tar.gz/ (4) + (5) + (6)
# 			scripts/
# 			RPMS/
# 			SRPMS/
# 	pkginfo/	(1) + (3)

	-mkdir tmp;
	-mkdir package_tmp;
	-mkdir package_tmp/RPMS;
	-mkdir package_tmp/SRPMS

#	Find RPMS listed in packing_list
	RPMSNEEDED=`cat packing_list |  perl -e 'while (<>) {if (/^#/) { } elsif (/^RPM:\s*(.*)/) {print "$$1 ";}}'` ;\
	for rpm in $$RPMSNEEDED; do \
		for dir in $(RPMSDIRS); do \
			RPMFILE=`ls $$dir/$$rpm* 2>/dev/null | grep -v '.src.rpm' | head -1`; \
			if [ ! -z $$RPMFILE ]; then \
				echo  $$RPMFILE; \
				cp $$RPMFILE package_tmp/RPMS; \
			fi; \
		done; \
	done; 

#	Check to make sure all RPMS got copied
	RPMSNEEDED=`cat packing_list |  perl -e 'while (<>) {if (/^#/) { } elsif (/^RPM:\s*(.*)/) {print "$$1 ";}}'` ;\
	for rpm in $$RPMSNEEDED; do \
		if [ ! -f package_tmp/RPMS/$$rpm* ]; then \
			echo -ne "Couldn't find the RPM $$rpm in any of the directories in \"";\
			echo -ne $(RPMSDIRS); \
			echo "\""; \
			exit 1; \
		fi; \
	done;

#	Find SRPMS listed in packing_list
	SRPMSNEEDED=`cat packing_list |  perl -e 'while (<>) {if (/^#/) { } elsif (/^SRPM:\s*(.*)/) {print "$$1 ";}}'` ;\
	for srpm in $$SRPMSNEEDED; do \
		for dir in $(SRPMSDIRS); do \
			SRPMFILE=`ls $$dir/$$srpm* | grep '.src.rpm' |  head -1`; \
			if [ ! -z $$SRPMFILE ]; then \
				echo $$SRPMFILE; \
				cp $$SRPMFILE package_tmp/SRPMS; \
			fi; \
		done; \
	done; 

#	Check to make sure all SRPMS got copied
	SRPMSNEEDED=`cat packing_list |  perl -e 'while (<>) {if (/^#/) { } elsif (/^SRPM:\s*(.*)/) {print "$$1 ";}}'` ;\
	for srpm in $$SRPMSNEEDED; do \
		if [ ! -f package_tmp/SRPMS/$$srpm* ]; then \
			echo -ne "Couldn't find the SRPM $$srpm in any of the directories in \"";\
			echo -ne $(SRPMSDIRS); \
			echo "\""; \
			exit 1; \
		fi; \
	done;


#	move scripts to tempdir
	-mkdir package_tmp/scripts
	tar -cBf - scripts/ | \
		(cd package_tmp; tar xBf -)
#	move pkginfo to tempdir
	-mkdir package_tmp/pkginfo
	tar -cBf - pkginfo/ | \
		(cd package_tmp; tar xBf -)
#	create packing_list from pkgdefs.tmpl
	cat pkgdefs.tmpl | $(SUBSTVARS_CMD) Vendor=$(VENDORNAME) Name=$(SERVICE) Version=$(VERSION) > package_tmp/packing_list

#	add RPMS, SRPMS entries into packing_list
	SORTEDRPMSNEW=`/usr/sausalito/bin/rpmsorter.sh package_tmp/RPMS/` ;\
	for rpms in $$SORTEDRPMSNEW; do \
		echo RPM: `basename $$rpms` >> package_tmp/packing_list; \
	done;

	for srpms in package_tmp/SRPMS/*; do \
		echo SRPM: `basename $$srpms` >> package_tmp/packing_list; \
	done;

#	Results
#	Create pkg file
	-mkdir package_results
	cd package_tmp; tar cBf ../package_results/$(VENDOR)-$(SERVICE).pkg .

#	add Size entry onto packing_list
	SIZE=`perl -e '@stats = stat("package_results/$(VENDOR)-$(SERVICE).pkg"); print @stats[7];'`; echo Size: $$SIZE >> package_tmp/packing_list

#	Create pkginfo directory
	(cd package_tmp; tar -cBf - pkginfo) | \
		(cd package_results; tar xBf -)
	cp package_tmp/packing_list package_results/pkginfo/

#	Results are in package_results
