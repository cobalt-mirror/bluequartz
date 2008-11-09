# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME=cobalt
SERVICE=pppoe

VERSION=1.0.1
RELEASE=31

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

# don't build ui, since it is already in base-network
BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=no

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
