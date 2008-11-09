# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueQuartz'
SERVICE=bluequartz

BUILD=20080101

VERSION=5200R
RELEASE=0.$(BUILD)BQ1

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=no
BUILDSRC=no

REQUIRES=httpd php iptables base-alpine-capstone /sbin/chkconfig swatch sendmail-cf m4 cyrus-sasl
REQUIRES_GLUE=base-email-capstone

include /usr/sausalito/devel/module.mk
