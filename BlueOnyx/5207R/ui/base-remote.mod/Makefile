# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Team BlueOnyx'
SERVICE=remote

VERSION=1.3.0
RELEASE=0BX02

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
BUILDUI=new
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=yes
REQUIRES=shellinabox base-remote-am

OBSOLETES=nuonce-mindterm

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
