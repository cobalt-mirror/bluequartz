# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=blueonyx

BUILD=20131204

VERSION=$(shell if [ `uname -m` = "x86_64" ]; then  echo -n "5108R"; else  echo -n "5107R"; fi)
RELEASE=4.$(BUILD)BX109

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=no
BUILDSRC=no

REQUIRES=httpd php iptables base-alpine-capstone /sbin/chkconfig swatch cyrus-sasl sendmail-cf m4 sendmail vixie-cron perl
REQUIRES_GLUE=base-email-capstone base-time-capstone base-user-capstone at dovecot php base-admserv-capstone

include /usr/sausalito/devel/module.mk
