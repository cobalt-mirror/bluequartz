ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CCEMODULE = sausalito-cce
CCERPMSPEC = sausalito-cce.spec
LIBRARY = libcce_common.a

RELEASE=1

CCE_V_MAJ=0
CCE_V_MIN=76
CCE_V_PATCH=9
CCE_VERSION=$(CCE_V_MAJ).$(CCE_V_MIN).$(CCE_V_PATCH)
VERSION=$(CCE_VERSION)

DIRS = common scalar conf codb ed cscp sessionmgr server client pam \
	ccewrap pperl

LIBS = \
	common/libcommon.a \
	scalar/libcce_scalar.a \
	conf/libcce_conf.a \
	codb/libcodb.a \
	ed/libcce_ed.a \
	cscp/libcscp.a \
	sessionmgr/libcce_session.a

all: version.mk cce-common server client pam ccewrap pperl

update_version: version.mk


version.mk: Makefile
	rm -f $@
	touch $@
	echo "CCE_V_MAJ=$(CCE_V_MAJ)" >> $@
	echo "CCE_V_MIN=$(CCE_V_MIN)" >> $@
	echo "CCE_V_PATCH=$(CCE_V_PATCH)" >> $@
	echo "CCE_VERSION=$(CCE_VERSION)" >> $@

server client: version.mk cce-common FORCE
	@echo
	@echo "********************************"
	@echo "* BUILDING CCE $@"
	@echo "********************************"
	@echo
	make -C $@ all

pam pperl: version.mk cce-common FORCE
	make -C $@ all

ccewrap: version.mk cce-common client FORCE
	make -C $@ all

ifeq ($(SLK_BUILD), yes)
rpm:
else
rpm: distclean specfile FORCE
endif
	touch /tmp/cce.make
	if [ X$(SLK_BUILD) = Xyes ]; then \
		make -C client locale_rpm; \
	else \
		tar -C .. \
		--exclude '*CVS*' \
		--exclude '*docs*' \
		-zcvf /tmp/sausalito-cce.tar.gz cce \
		&> /dev/null; \
		sleep 10; \
		rpm -ta /tmp/sausalito-cce.tar.gz; \
	fi
	-@if ! test -d rpms ; then mkdir rpms ; fi
	cp `find /usr/src/redhat/RPMS -type f \
		-newer /tmp/cce.make` rpms
	find rpms -type f -printf "RPM: %P\n" > packing_list

specfile: $(CCERPMSPEC).in
	sed -e 's/XX_VER_XX/$(CCE_VERSION)/' \
	    -e 's/XX_REL_XX/$(RELEASE)/' $(CCERPMSPEC).in > $(CCERPMSPEC)

rpm_upload:
	/usr/sausalito/bin/ftp_put rpms/*.rpm -d $(RPM_DIR)

mod_locale:
	make -C client mod_locale

install_locale:
	make -C client install_locale

install: all server-inst client-inst pam-inst ccewrap-inst pperl-inst

server-inst client-inst pam-inst ccewrap-inst pperl-inst: %-inst: %
	make -C $< install

depend uninstall junk:
	for a in $(DIRS); do \
		echo "Making $(MAKECMDGOALS) in $$a"; \
		make -C $$a $(MAKECMDGOALS) || exit 1; \
		echo; \
		echo; \
	done

clean: version.mk FORCE
	for a in $(DIRS); do \
		echo "Making clean in $$a"; \
		make -C $$a clean || exit 1; \
		echo; \
		echo; \
	done
	/bin/rm -rf .objs *.a
	/bin/rm -rf rpms
	/bin/rm -f $(CCERPMSPEC)

distclean: clean
	/bin/rm version.mk

common/libcommon.a: FORCE
	make -C common all

scalar/libcce_scalar.a: FORCE
	make -C scalar all

conf/libcce_conf.a: FORCE
	make -C conf all

codb/libcodb.a: FORCE
	make -C codb all

ed/libcce_ed.a: FORCE
	make -C ed all

cscp/libcscp.a: FORCE
	make -C cscp all

sessionmgr/libcce_session.a: FORCE
	make -C sessionmgr all

cce-common: $(LIBRARY)

$(LIBRARY): $(LIBS)
	@echo
	@echo "********************************"
	@echo "* BUILDING CCE $@"
	@echo "********************************"
	@echo
	# is all this really necessary?
	for a in $(LIBS); do \
		mkdir -p .objs; \
		(cd .objs; /bin/rm -f *.o ; ar x ../$$a); \
		ar crv $(LIBRARY) .objs/*.o; \
	done	
	ranlib $(LIBRARY)

FORCE:

#ifdef CCETOPDIR
#include $(CCETOPDIR)/devel/package.mk
#else
#include $(CCEDIR)/devel/package.mk
#endif
