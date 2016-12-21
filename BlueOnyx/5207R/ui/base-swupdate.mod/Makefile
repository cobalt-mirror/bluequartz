# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=swupdate

VERSION=1.6.1
RELEASE=0BX10

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=new
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=no

REQUIRES = perl-Sort-Versions wget lynx php-soap sausalito-palette >= 0.5.2-0BX21 base-api-capstone base-istat-capstone perl-Sys-Hostname-FQDN base-support-capstone file perl-List-MoreUtils swatch

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
