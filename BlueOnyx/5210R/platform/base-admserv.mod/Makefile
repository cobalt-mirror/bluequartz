# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=admserv

VERSION=2.3.0
RELEASE=0BX3

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=no
BUILDGLUE=yes
BUILDLOCALE=no
BUILDSRC=no

REQUIRES=httpd sausalito-cce-client sausalito-cce-server sausalito-i18n php-mysqlnd 
REQUIRES_GLUE=openssl httpd mod_ssl sausalito-cce-client sausalito-cce-server sausalito-i18n php php-cli php-common php-devel php-fpm php-gd php-intl php-mbstring php-mysqlnd php-pdo php-pear php-process php-soap php-xml mod_perl
OBSOLETES=php-mysql

include /usr/sausalito/devel/module.mk

