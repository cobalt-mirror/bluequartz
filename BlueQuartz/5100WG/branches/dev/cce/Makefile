# $Id: Makefile 229 2003-07-18 20:22:20Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
# Top level Makefile for CCE

# this must be before includes
CCE_TOPDIR := $(CURDIR)

include cce.mk
#do not include dynamic.mk

# edit the CCE version information HERE ONLY
CCE_V_MAJOR = 0
CCE_V_MINOR = 81
CCE_V_MICRO = 0
CCE_VERSION = $(CCE_V_MAJOR).$(CCE_V_MINOR).$(CCE_V_MICRO)
RPM_RELEASE = 1
RPMSPEC = redhat/cce.spec

DIRS = common client server tools

all test debug: dynamic.mk .depend
	for a in $(DIRS); do $(MAKE) -C $$a $(MAKECMDGOALS) || exit 1; done

install: all
	$(MAKE) -C client install
	$(MAKE) -C server install
	$(MAKE) -C tools install
	mkdir -p $(SYS_INITDIR)
	$(INSTALL_BIN) redhat/cced.init $(SYS_INITDIR)
	mkdir -p $(SYS_PAMDIR)
	$(INSTALL_FILE) redhat/cced.pam $(SYS_PAMDIR)/cced

depend .depend: dynamic.mk
	for a in $(DIRS); do $(MAKE) -C $$a depend || exit 1; done
	touch .depend

clean: dynamic.mk
	for a in $(DIRS); do $(MAKE) -C $$a clean || exit 1; done
	$(MAKE) -C test clean
	$(RM) $(RPMSPEC) .depend

veryclean: clean
	$(RM) dynamic.mk

rpm: clean $(RPMSPEC)
	TMPDIR=`mktemp -d /tmp/cce-build-$$$$.XXXXXX`; \
	TMPFILE=$$TMPDIR/cce-$(CCE_VERSION)-$(RPMRELEASE).tar.gz; \
	tar -C .. --exclude '*CVS*' -zcvf $$TMPFILE cce &> /dev/null; \
	rpm -ta $$TMPFILE;

$(RPMSPEC): Makefile
	sed -e 's/@@VERSION@@/$(CCE_VERSION)/' \
	    -e 's/@@RELEASE@@/$(RPM_RELEASE)/' \
	    $(RPMSPEC).in > $(RPMSPEC)

dynamic.mk: Makefile
	@$(RM) $@
	@( \
		echo 'CCE_TOPDIR=$(CCE_TOPDIR)'; \
		echo 'CCE_V_MAJOR=$(CCE_V_MAJOR)'; \
		echo 'CCE_V_MINOR=$(CCE_V_MINOR)'; \
		echo 'CCE_V_MICRO=$(CCE_V_MICRO)'; \
		echo 'CCE_VERSION=$(CCE_VERSION)'; \
		echo 'ifneq ($$(wildcard $$(CCE_TOPDIR)/cce.mk),)'; \
		echo 'include $$(CCE_TOPDIR)/cce.mk'; \
		echo 'endif'; \
	) > $@
	@echo "$@ created"
