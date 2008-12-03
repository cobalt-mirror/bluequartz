# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=documentation

VERSION=1.0.1
RELEASE=31BQ12

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

# need to exclude all directories that have rpm spec files of their own in
# or rpm gets confused
EXTRAEXCLUDES=--exclude 'pkgs'

BUILDUI=yes
BUILDLOCALE=yes
BUILDGLUE=no
BUILDSRC=no

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
