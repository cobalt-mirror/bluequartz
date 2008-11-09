# Makefile to build Cobalt Migration Utility (CMU)
# For the CPR phase of the project
#
# usage: make <product>
#
# $Id: Makefile,v 1.9 2003/09/29 15:11:14 jeffb Exp $

CWD = `pwd`

# Some definitions
INSTALL = /usr/bin/install

VERSION   =   2.07

BUILD_DIR =   /home/tmp/shell-tools
TMP_DIR   =   $(BUILD_DIR)/shell-tools
TMPTAR    =   $(BUILD_DIR)/shell-tools.tar.gz
SHELLUSER =   root
PERLDIR   =   $(TMP_DIR)/perl
SCRIPTDIR =   $(TMP_DIR)/scripts
CONFDIR   =   $(TMP_DIR)/conf

SPEC_FILE =   $(TMP_DIR)/shell-tools.spec
SPEC_IN  =   shell-tools.spec.in
MAKE_FILE =   $(TMP_DIR)/Makefile
MAKE_IN  =   Makefile.in

CP		= /bin/cp

default:
	@echo "For Shell tools: make product"

clean:
	@echo "Clean up before building"
	/bin/rm -rf $(BUILD_DIR)
	/bin/mkdir -p $(PERLDIR)
	/bin/mkdir -p $(SCRIPTDIR)
	/bin/mkdir -p $(CONFDIR)

cmu: clean
	@echo "Building CMU...."
	$(CP) -R $(CWD)/perl/* $(PERLDIR)
	$(CP) -R $(CWD)/conf/* $(CONFDIR)
	$(CP) -R $(CWD)/scripts/* $(SCRIPTDIR)
	$(CP) $(CWD)/ignore $(TMP_DIR)

raqxtr: cmu
	sed -e 's/\[PRODUCT\]/RaQXTR/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/RaQXTR/' < $(MAKE_IN) > $(MAKE_FILE)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) shell-tools	
	rpm -ta $(TMPTAR)

raq550: cmu
	sed -e 's/\[PRODUCT\]/RaQ550/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/RaQ550/' < $(MAKE_IN) > $(MAKE_FILE)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) shell-tools	
	rpm -ta $(TMPTAR)

qube3: cmu 
	sed -e 's/\[PRODUCT\]/Qube3/' < $(SPEC_IN) > $(SPEC_FILE)
	sed -e 's/\[PRODUCT\]/Qube3/' < $(MAKE_IN) > $(MAKE_FILE)
	$(CP) conf/Qube3.cobaltBase.xml $(BASE_CONF)
	perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(SPEC_FILE) $(MAKE_FILE) $(BASE_CONF)
	/bin/tar -X $(TMP_DIR)/ignore -C $(BUILD_DIR) -czvf $(TMPTAR) shell-tools	
	rpm -ta $(TMPTAR)
	# perl -pi -e 's#\[VERSION\]#$(VERSION)#' $(PKG_DIR)/Qube3/packing_list
