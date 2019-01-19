# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=admserv

VERSION=2.3.0
RELEASE=0BX1

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=no
BUILDSRC=no

REQUIRES=httpd sausalito-cce-client >= 0.99.2-1BX01 sausalito-cce-server >= 0.99.2-1BX01 sausalito-i18n >= 0.81.2 php-mysqlnd
REQUIRES_GLUE=openssl httpd mod_ssl sausalito-cce-client >= 0.99.2-1BX01 sausalito-cce-server >= 0.99.2-1BX01 sausalito-i18n >= 0.81.2
OBSOLETES=php-mysql

include /usr/sausalito/devel/module.mk

