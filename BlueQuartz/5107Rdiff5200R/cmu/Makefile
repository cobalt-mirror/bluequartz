# Makefile to build Cobalt Migration Utility (CMU)
# For the CPR phase of the project
#
# usage: make <product>
#
# $Id: Makefile 1513 2010-08-08 12:43:32Z shibuya $

CWD = `pwd`

# Some definitions
INSTALL = /usr/bin/install

VERSION   =   2.68

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

QUBE3_SCRIPTS = \
	$(wildcard scripts/Qube3*.pl)

QUBE2_SCRIPTS = \
	$(wildcard scripts/Qube2*.pl)

QUBE2_LIBS = \
	Qube2.pm 

ADJUST_SCRIPTS = \
	$(wildcard scripts/RaQ*adjust.pl scripts/5*00Radjust.pl scripts/5106Radjust.pl)

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
	/bin/tar --exclude '.svn' -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) cpr
	$(RPMBUILD) -ta $(TMPTAR)

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

all:: raq2 raq3 raq4 raqxtr raq550 5100r 5200r qube2 qube3

raq1:: cmu
	(cd $(TMP_DIR);sed -e 's/\[PRODUCT\]/RaQ1/' < $(SPEC_FILE) > $(SPEC_NEW))
	(cd $(TMP_DIR);mv $(SPEC_NEW) $(SPEC_FILE))
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) CMU	
	$(RPMBUILD) -ta $(TMPTAR)
