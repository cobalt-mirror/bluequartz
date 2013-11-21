# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueQuartz'
SERVICE=bluequartz

BUILD=20110503

VERSION=5200R
RELEASE=1.$(BUILD)BQ39

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=no
BUILDSRC=no

REQUIRES=httpd php iptables base-alpine-capstone /sbin/chkconfig swatch cyrus-sasl postfix vixie-cron
REQUIRES_GLUE=base-email-capstone base-time-capstone base-user-capstone

include /usr/sausalito/devel/module.mk
