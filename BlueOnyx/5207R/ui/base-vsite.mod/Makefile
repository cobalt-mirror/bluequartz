# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=vsite

VERSION=4.0
RELEASE=0BX47

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=new
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=yes

REQUIRES= base-vsite-am base-email-capstone >= 1.3.0-0BQ16 sausalito-i18n >= 0.70.2-86BQ16 sausalito-cce-server >= 0.80.2-1BQ34 base-admserv-capstone >= 1.0.1-0BQ11 perl-handler-utils >= 1.3.0-11BQ12 mod_suphp mod_authnz_external
OBSOLETES= nuonce-hostsfix

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
