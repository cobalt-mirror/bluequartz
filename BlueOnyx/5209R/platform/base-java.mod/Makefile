# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Team BlueOnyx'
SERVICE=java

VERSION=5.0.0
RELEASE=0BX06

# add a buildarch if desired
#BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=new
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=yes

REQUIRES= tomcat tomcat-admin-webapps tomcat-docs-webapp tomcat-javadoc tomcat-jsp-2.2-api tomcat-jsvc tomcat-lib tomcat-servlet-3.0-api tomcat-webapps tomcatjss php-process jakarta-commons-httpclient
OBSOLETES= mod_jk

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
