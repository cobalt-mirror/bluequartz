# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx and Bluapp AB'
SERVICE=email

VERSION=1.6.0
RELEASE=0BX16

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=yes

REQUIRES = procmail sendmail dovecot poprelayd base-blueonyx-glue perl-MIME-Lite >= 3.023-2 perl-Email-Date-Format >= 1.002-1 base-email-am blueonyx-z-push >= 1.3 php-pear-Mail >= 1.1.4

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
