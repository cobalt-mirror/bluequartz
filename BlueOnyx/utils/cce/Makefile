# $Id: Makefile 1162 2008-06-28 18:29:44Z mstauber $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Top level Makefile for CCE

# this must be before includes
CCE_TOPDIR = $(shell pwd)

include rules.mk
# do not include dynamic.mk
include /usr/sausalito/devel/defines.mk

CCEMODULE = sausalito-cce
CCERPMSPEC = $(CCEMODULE).spec

CCE_V_MAJ=0
CCE_V_MIN=81
CCE_V_PATCH=1
CCE_VERSION=$(CCE_V_MAJ).$(CCE_V_MIN).$(CCE_V_PATCH)
RPMRELEASE=1BX05

DIRS = common scalar conf codb ed cscp sessionmgr server client pam pam_abl \
	ccewrap pperl

COMMONLIB = libcce_common.a
COMMONLIB_DIRS = common scalar conf codb ed cscp sessionmgr
COMMONLIB_FILES = $(patsubst %,/%.a,$(COMMONLIB_DIRS))
COMMONLIB_LIBS = $(join $(COMMONLIB_DIRS), $(COMMONLIB_FILES))
TIMEFILE:=/tmp/make.tmp

.PHONY: all debug depend server client tools 
all: depend $(COMMONLIB) server client tools

depend: .depend dynamic.mk
.depend: 
	@echo "********************************"
	@echo "* Making depend"
	@echo "********************************"
	@echo
	@for a in $(DIRS); do \
		echo $(MAKE) -C $$a depend || exit 1; \
	done
	@touch .depend

dynamic.mk: Makefile
	@rm -f $@
	@( \
		echo 'CCE_TOPDIR=$(CCE_TOPDIR)'; \
		echo 'CCE_V_MAJ=$(CCE_V_MAJ)'; \
		echo 'CCE_V_MIN=$(CCE_V_MIN)'; \
		echo 'CCE_V_PATCH=$(CCE_V_PATCH)'; \
		echo 'CCE_VERSION=$(CCE_VERSION)'; \
		echo 'include $$(CCE_TOPDIR)/rules.mk'; \
		echo 'CFLAGS += -fPIC'; \
	) > $@
	@echo "$@ created"

common/common.a: FORCE
	make -C common
scalar/scalar.a: FORCE
	make -C scalar
conf/conf.a: FORCE
	make -C conf
codb/codb.a: FORCE
	make -C codb
ed/ed.a: FORCE
	make -C ed
cscp/cscp.a: FORCE
	make -C cscp
sessionmgr/sessionmgr.a: FORCE
	make -C sessionmgr

$(COMMONLIB): $(COMMONLIB_LIBS)
	@echo
	@echo "********************************"
	@echo "* BUILDING CCE $@"
	@echo "********************************"
	@echo
	@mkdir -p .objs
	@for i in $?; do \
		(cd .objs; /bin/rm -f *.o ; ar x ../$$i); \
		ar rcsv $@ .objs/*.o; \
	done
	@rm -rf .objs

server client: dynamic.mk $(COMMONLIB) FORCE
	@echo
	@echo "********************************"
	@echo "* BUILDING CCE $@"
	@echo "********************************"
	@echo
	@$(MAKE) -C $@ all

tools: pam pam_abl ccewrap pperl

pam: dynamic.mk $(COMMONLIB) FORCE
	$(MAKE) -C $@ all

pam_abl: dynamic.mk $(COMMONLIB) FORCE
	$(MAKE) -C $@ all

pperl: dynamic.mk $(COMMONLIB) FORCE
	$(MAKE) -C $@ all

ccewrap: dynamic.mk $(COMMONLIB) client FORCE
	$(MAKE) -C $@ all

test:
#	$(MAKE) -C server test
	$(MAKE) -C common test
	$(MAKE) -C scalar test
#	$(MAKE) -C ed test
	$(MAKE) -C cscp test
	$(MAKE) -C conf test
	$(MAKE) -C codb test
#	$(MAKE) -C client test
	$(MAKE) -C test

clean: dynamic.mk
	@for a in $(DIRS); do \
		echo; \
		echo "********************************"; \
		echo "* Making clean in $$a"; \
		echo "********************************"; \
		$(MAKE) -C $$a clean || exit 1; \
	done
	$(RM) -rf .objs $(COMMONLIB)
	$(RM) -rf rpms $(CCERPMSPEC)
	$(RM) -f .depend
	find . -name core | xargs $(RM) -f
	$(RM) -rf rpms srpms as_rpms as_srpms
	find . -type f \( -name \*~ -o -name \*.o -o \
			   -name \#\* -o -name \*.mo -o -name core \) | xargs $(RM) -f
	$(MAKE) -C test clean

distclean: clean
	/bin/rm dynamic.mk

#
# build rules 
#
ifeq ($(SLK_BUILD), yes)
rpm:
else
rpm: distclean specfile FORCE
endif
	touch $(TIMEFILE)
	if [ X$(SLK_BUILD) = Xyes ]; then \
		$(MAKE) -C client locale_rpm; \
	else \
		tar -C .. \
		--exclude '*CVS*' \
		--exclude '*docs*' \
		--exclude '.svn' \
		-zcvf /tmp/sausalito-cce.tar.gz cce \
		&> /dev/null; \
		sleep 10; \
		$(RPMBUILD) -ta /tmp/sausalito-cce.tar.gz; \
	fi
	-@mkdir as_rpms || /bin/true
	cp `find $(RPM_TOPDIR)/RPMS -follow -type f -newer $(TIMEFILE)` as_rpms
	-@mkdir as_srpms || /bin/true
	cp `find $(RPM_TOPDIR)/SRPMS -follow -type f -newer $(TIMEFILE)` as_srpms
	-@/bin/rm -f $(TIMEFILE)

specfile: $(CCERPMSPEC).in
	sed -e 's/XX_VER_XX/$(CCE_VERSION)/' \
	    -e 's/XX_USER_HTTPD_XX/$(USER_HTTPD)/' \
	    -e 's/XX_REL_XX/$(RPMRELEASE)/' $(CCERPMSPEC).in > $(CCERPMSPEC)

rpm_upload:
	/usr/sausalito/bin/ftp_put rpms/*.rpm -d $(RPM_DIR)

mod_locale:
	$(MAKE) -C client mod_locale

install_locale:
	$(MAKE) -C client install_locale

install: all server-inst client-inst pam-inst pam_abl-inst ccewrap-inst pperl-inst

server-inst client-inst pam-inst pam_abl-inst ccewrap-inst pperl-inst: %-inst: %
	$(MAKE) -C $< install

uninstall junk:
	for a in $(DIRS); do \
		echo "Making $(MAKECMDGOALS) in $$a"; \
		$(MAKE) -C $$a $(MAKECMDGOALS) || exit 1; \
		echo; \
		echo; \
	done

update_version: dynamic.mk

FORCE:
