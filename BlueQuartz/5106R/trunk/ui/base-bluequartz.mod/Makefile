# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueQuartz'
SERVICE=bluequartz

BUILD=20080607

VERSION=5106R
RELEASE=1.$(BUILD)BQ82

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=no
BUILDSRC=no

REQUIRES=httpd php iptables base-alpine-capstone /sbin/chkconfig swatch sendmail-cf m4 cyrus-sasl
REQUIRES_GLUE=base-email-capstone base-time-capstone >= 1.0.1-62BQ11

include /usr/sausalito/devel/module.mk
