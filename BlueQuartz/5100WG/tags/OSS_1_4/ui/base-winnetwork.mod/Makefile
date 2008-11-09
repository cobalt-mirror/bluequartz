# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME=cobalt
SERVICE=winnetwork

VERSION=1.0.1
RELEASE=28

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=no

RPMSDIRS=/usr/src/redhat/RPMS/i386
SRPMSDIRS=/usr/src/redhat/SRPMS

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk

install:
	mkdir -p /home/profiles
	chmod 755 /home/profiles
	mkdir -p $(PREFIX)/sbin
	install -m 700 ./sbin/addWindowsMachine.pl $(PREFIX)/sbin/addWindowsMachine.pl
