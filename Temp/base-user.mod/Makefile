# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueQuartz'
SERVICE=user

VERSION=1.1.0
RELEASE=111BQ33

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=no

REQUIRES= base-email-capstone >= 1.3.0-0BQ16
REQUIRES= sausalito-cce-server >= 0.80.3-1BQ34
REQUIRES= base-admserv-capstone >= 1.0.1-0BQ11
REQUIRES= sausalito-i18n >= 0.70.2-86BQ16
REQUIRES= authconfig >= 5.3.12-2.el5

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
