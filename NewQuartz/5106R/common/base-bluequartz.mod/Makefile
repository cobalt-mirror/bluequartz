# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueQuartz'
SERVICE=bluequartz

BUILD=20081113

VERSION=5106R
RELEASE=2.$(BUILD)BQ86

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=no
BUILDSRC=no

REQUIRES=httpd php iptables base-alpine-capstone /sbin/chkconfig swatch sendmail-cf m4 cyrus-sasl
REQUIRES_GLUE=base-email-capstone base-time-capstone >= 1.0.1-62BQ11 base-user-capstone >= 1.2.0-0BQ1 base-user-glue >= 1.2.0-0BQ1 at

include /usr/sausalito/devel/module.mk
