# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=user

VERSION=1.3.0
RELEASE=0BX12

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=no

REQUIRES= base-email-capstone >= 1.3.0-0BQ16 sausalito-cce-server >= 0.80.3-1BQ34 base-admserv-capstone >= 1.0.1-0BQ11 sausalito-i18n >= 0.70.2-86BQ16 authconfig >= 5.3.12-2.el5 sausalito-i18n >= 0.70.2-86BQ16 base-blueonyx-capstone >= 2.20080501 perl-Unix-ConfigFile >= 0.06-SOL1

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
