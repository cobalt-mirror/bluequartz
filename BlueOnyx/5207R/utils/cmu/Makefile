# Makefile to build Cobalt Migration Utility (CMU)
# For the CPR phase of the project
#
# usage: make <product>
#
# $Id: Makefile 1066 2008-01-27 09:21:48Z shibuya $

CWD = `pwd`

# Some definitions
INSTALL = /usr/bin/install

VERSION   =   2.92

BUILD_DIR =   /tmp/cmu
TMP_DIR   =   $(BUILD_DIR)/cpr
TMPTAR    =   $(BUILD_DIR)/cpr.tar.gz
PKG_DIR	  =	  $(TMP_DIR)/packages
CMUUSER   =   $(shell id -u)
PERLDIR   =   $(TMP_DIR)/perl
SCRIPTDIR =   $(TMP_DIR)/scripts
CONFDIR   =   $(TMP_DIR)/etc/cmu

SPEC_FILE =   $(TMP_DIR)/cobalt-cmu.spec
SPEC_IN  =   cobalt-cmu.spec.in
MAKE_FILE =   $(TMP_DIR)/Makefile
MAKE_IN  =   Makefile.in

RPMBUILD=$(shell which rpmbuild>/dev/null 2>&1&&echo rpmbuild||echo rpm)

CP		= /bin/cp

BASE_CONF =   $(TMP_DIR)/conf/cobaltBase.xml

RAQ2_SCRIPTS = \
	$(wildcard scripts/RaQ2*.pl)

RAQ2_LIBS = \
	RaQ2.pm \
	RaQUtil.pm 

RAQ3_SCRIPTS = \
	$(wildcard scripts/RaQ3*.pl)

RAQ3_LIBS = \
	RaQ3.pm \
	RaQUtil.pm 

RAQ4_SCRIPTS = \
	$(wildcard scripts/RaQ4*.pl)

RAQ4_LIBS = \
	RaQ4.pm \
	RaQUtil.pm 

RAQXTR_SCRIPTS = \
	$(wildcard scripts/RaQXTR*.pl)

RAQXTR_LIBS = \
	RaQXTR.pm \
	RaQUtil.pm 

RAQ550_SCRIPTS = \
	$(wildcard scripts/RaQ550*.pl)

RAQ550_LIBS = \
	RaQUtil.pm 

5100R_SCRIPTS = \
	$(wildcard scripts/5100R*.pl)

5100R_LIBS = \
	RaQUtil.pm

5200R_SCRIPTS = \
	$(wildcard scripts/5200R*.pl)

5200R_LIBS = \
	RaQUtil.pm

5106R_SCRIPTS = \
	$(wildcard scripts/5106R*.pl)

5106R_LIBS = \
	RaQUtil.pm

5107R_SCRIPTS = \
	$(wildcard scripts/5107R*.pl)

5107R_LIBS = \
	RaQUtil.pm

5108R_SCRIPTS = \
	$(wildcard scripts/5108R*.pl)

5108R_LIBS = \
	RaQUtil.pm

5207R_SCRIPTS = \
	$(wildcard scripts/5207R*.pl)

5207R_LIBS = \
	RaQUtil.pm

5208R_SCRIPTS = \
	$(wildcard scripts/5208R*.pl)

5208R_LIBS = \
	RaQUtil.pm

5209R_SCRIPTS = \
	$(wildcard scripts/5209R*.pl)

5209R_LIBS = \
	RaQUtil.pm

5160R_SCRIPTS = \
	$(wildcard scripts/5160R*.pl)

5160R_LIBS = \
	RaQUtil.pm

5161R_SCRIPTS = \
	$(wildcard scripts/5160R*.pl)

5161R_LIBS = \
	RaQUtil.pm


TLAS1HE_SCRIPTS = \
	$(wildcard scripts/TLAS1HE*.pl)

TLAS1HE_LIBS = \
	RaQUtil.pm

TLAS2_SCRIPTS = \
	$(wildcard scripts/TLAS2*.pl)

TLAS2_LIBS = \
        RaQUtil.pm

QUBE3_SCRIPTS = \
	$(wildcard scripts/Qube3*.pl)

QUBE2_SCRIPTS = \
	$(wildcard scripts/Qube2*.pl)

QUBE2_LIBS = \
	Qube2.pm 

ADJUST_SCRIPTS = \
	$(wildcard scripts/RaQ*adjust.pl scripts/5*0*Radjust.pl scripts/TLAS*HEadjust.pl scripts/TLAS2adjust.pl)

BASE_SCRIPTS = \
	cmuConfig \
	cmuExport \
	cmuImport \
	cmuConflict \
	cmuAdjust \
    dnsImport

default::
	@echo "For Cobalt Migration Utility: make product"

clean::
	@echo "Clean up before building"
	-find . -type f \( -name \*~ -o -name \*.o -o \
			   -name \#\* -o -name \*.mo \) | xargs rm -f
	/bin/rm -rf $(BUILD_DIR)
	/bin/mkdir -p $(PERLDIR)
	/bin/mkdir -p $(TMP_DIR)/scripts
	/bin/mkdir -p $(TMP_DIR)/man
	/bin/mkdir -p $(TMP_DIR)/conf
	/bin/mkdir -p $(TMP_DIR)/packages

base::
	for t in $(BASE_SCRIPTS) ; do \
		$(INSTALL) -o $(CMUUSER) -m0700 scripts/$$t $(SCRIPTDIR)/$$t ; \
	done \

adjust::
	for t in $(ADJUST_SCRIPTS) ; do \
		$(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
	done \

cmu:: clean
	@echo "Building CMU...."
	$(CP) -R $(CWD)/man/* $(TMP_DIR)/man
	$(CP) -R $(CWD)/perl_modules/* $(PERLDIR)
	$(CP) -R $(CWD)/packages/* $(PKG_DIR)
	$(CP) $(CWD)/ignore $(TMP_DIR)
	$(CP) $(CWD)/conf/cmuConfig.xml $(TMP_DIR)/conf
	$(CP) $(CWD)/conf/csoft.xml $(TMP_DIR)/conf

raq2:: cmu base
	for t in $(RAQ2_SCRIPTS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(RAQ2_LIBS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/RaQ2/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/RaQ2/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/RaQ2.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr	
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' \
			$(PKG_DIR)/RaQ2/packing_list $(PKG_DIR)/RaQ2/uninstall_me
	(cd $(PKG_DIR)/RaQ2;make)

raq3:: cmu base adjust
	for t in $(RAQ3_SCRIPTS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(RAQ3_LIBS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/RaQ3/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/RaQ3/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/RaQ3.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr	
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' \
			$(PKG_DIR)/RaQ3/packing_list $(PKG_DIR)/RaQ3/uninstall_me
	(cd $(PKG_DIR)/RaQ3;make)

raq4:: cmu base adjust
	for t in $(RAQ4_SCRIPTS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(RAQ4_LIBS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/RaQ4/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/RaQ4/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/RaQ4.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr	
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' \
			$(PKG_DIR)/RaQ4/packing_list $(PKG_DIR)/RaQ4/uninstall_me
	(cd $(PKG_DIR)/RaQ4;make)

raqxtr:: cmu base adjust
	for t in $(RAQXTR_SCRIPTS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(RAQXTR_LIBS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/RaQXTR/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/RaQXTR/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/RaQXTR.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr	
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/RaQXTR/packing_list
	(cd $(PKG_DIR)/RaQXTR;make)

raq550:: cmu base adjust
	for t in $(RAQ550_SCRIPTS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(RAQ550_LIBS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/RaQ550/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/RaQ550/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/RaQ550.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr	
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/RaQ550/packing_list
	(cd $(PKG_DIR)/RaQ550;make)

5100r:: cmu base adjust
	for t in $(5100R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5100R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5100R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5100R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5100R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5100R/packing_list
	(cd $(PKG_DIR)/5100R;make)

5200r:: cmu base adjust
	for t in $(5200R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5200R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5200R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5200R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5200R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5200R/packing_list
	(cd $(PKG_DIR)/5200R;make)

5106r:: cmu base adjust
	for t in $(5106R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5106R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5106R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5106R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5106R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5106R/packing_list
	(cd $(PKG_DIR)/5106R;make)

5107r:: cmu base adjust
	for t in $(5107R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5107R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5107R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5107R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5107R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5107R/packing_list
	(cd $(PKG_DIR)/5107R;make)

5108r:: cmu base adjust
	for t in $(5108R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5108R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5108R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5108R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5108R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5108R/packing_list
	(cd $(PKG_DIR)/5108R;make)

5207r:: cmu base adjust
	for t in $(5207R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5207R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5207R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5207R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5207R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5207R/packing_list
	(cd $(PKG_DIR)/5207R;make)

5208r:: cmu base adjust
	for t in $(5208R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5208R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5208R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5208R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5208R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5208R/packing_list
	(cd $(PKG_DIR)/5208R;make)

5209r:: cmu base adjust
	for t in $(5209R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5209R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5209R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5209R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5209R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5209R/packing_list
	(cd $(PKG_DIR)/5209R;make)

5160r:: cmu base adjust
	for t in $(5160R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5160R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5160R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5160R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5160R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5160R/packing_list
	(cd $(PKG_DIR)/5160R;make)

5161r:: cmu base adjust
	for t in $(5161R_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(5161R_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/5161R/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/5161R/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/5161R.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5161R/packing_list
	(cd $(PKG_DIR)/5161R;make)

tlas1he:: cmu base adjust
	for t in $(TLAS1HE_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(TLAS1HE_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/TLAS1HE/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/TLAS1HE/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/TLAS1HE.turbolinuxBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5100R/packing_list
	(cd $(PKG_DIR)/TLAS1HE;make)

tlas2:: cmu base adjust
	for t in $(TLAS2_SCRIPTS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(TLAS2_LIBS) ; do \
	 $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/TLAS2/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/TLAS2/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/TLAS2.turbolinuxBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/5100R/packing_list
	(cd $(PKG_DIR)/TLAS2;make)

qube2:: cmu base
	for t in $(QUBE2_SCRIPTS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	for t in $(QUBE2_LIBS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0600 lib/$$t $(PERLDIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/Qube2/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/Qube2/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/Qube2.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr	
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' \
			$(PKG_DIR)/Qube2/packing_list $(PKG_DIR)/Qube2/uninstall_me
	(cd $(PKG_DIR)/Qube2;make)

qube3:: cmu base
	for t in $(QUBE3_SCRIPTS) ; do \
         $(INSTALL) -o $(CMUUSER) -m0700 $$t $(TMP_DIR)/$$t ; \
    done
	sed -e 's/\[PRODUCT\]/Qube3/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/Qube3/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/Qube3.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr	
	$(RPMBUILD) -ta $(TMPTAR)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/Qube3/packing_list
	(cd $(PKG_DIR)/Qube3;make)

all:: raq2 raq3 raq4 raqxtr raq550 5100r 5106r 5107r 5108r 5207r 5208r 5209r 5160r 5161r 5200r tlas1he tlas2 qube2 qube3

raq1:: cmu
	(cd $(TMP_DIR);sed -e 's/\[PRODUCT\]/RaQ1/' < $(SPEC_FILE) > $(SPEC_NEW))
	(cd $(TMP_DIR);mv $(SPEC_NEW) $(SPEC_FILE))
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) CMU	
	$(RPMBUILD) -ta $(TMPTAR)
