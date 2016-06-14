# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=vsite

VERSION=4.2
RELEASE=0BX65

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=new
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=no

REQUIRES= base-email-capstone sausalito-i18n sausalito-cce-server base-admserv-capstone perl-handler-utils mod_suphp mod_authnz_external ImageMagick icu libicu-devel libmcrypt libmhash libmhash-devel libssh2 libssh2-devel libssh2-docs aspell postgresql postgresql-libs freetds freetds-doc libdb4 perl-LWP-Protocol-https e2fsprogs
OBSOLETES= nuonce-hostsfix

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
