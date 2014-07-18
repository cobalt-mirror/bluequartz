# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=blueonyx

SUBVER=BETA01
BUILD=$(shell date --rfc-3339=date | sed s/-//g)$(SUBVER)

VERSION=5209R
RELEASE=4.$(BUILD)

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=no
BUILDSRC=no

REQUIRES=httpd php iptables base-alpine-capstone /sbin/chkconfig swatch cyrus-sasl sendmail-cf m4 sendmail perl cronie cronie-noanacron crontabs
REQUIRES_GLUE=base-email-capstone base-time-capstone base-user-capstone at dovecot php base-admserv-capstone base-support-capstone

include /usr/sausalito/devel/module.mk
