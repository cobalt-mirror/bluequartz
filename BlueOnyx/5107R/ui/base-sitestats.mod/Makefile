# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=sitestats

VERSION=1.0
RELEASE=124BX24

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=yes

REQUIRES_UI=coreutils httpd

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_SBIN=$(INSTALL) -m 700
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
