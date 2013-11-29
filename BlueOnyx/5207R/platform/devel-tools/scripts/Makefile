include ../rules/defines.mk

SCRIPTS=cpan2rpm cvs_checkout cvs_checkout_multibranch po_automerge.pl hup mod_rpmize makePkg make_release_spec ftp_put makePackageVars make_release make_install dumpCaps mendocino_package checkout_product.pl verifyRelease.pl AutoBuild.pl buildFromBTO.pl reencode.pl packsort.pl

all dep clean:

install: 
	-mkdir -p $(CCEBINDIR)
	$(INSTALL) $(INSTALL_SCRIPTFLAGS) $(SCRIPTS) $(CCEBINDIR)
	-mkdir -p $(CCEPERLDIR)
	$(INSTALL) $(INSTALL_LIBFLAGS) Devel.pm Build.pm BTO.pm $(CCEPERLDIR)
