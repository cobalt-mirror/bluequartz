# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Team BlueOnyx'
SERVICE=java

VERSION=3.0.0
RELEASE=10

# add a buildarch if desired
#BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=yes

REQUIRES= apache-jasper apache-jasper-javadoc apache-tomcat-apis base-java-am base-java-glue base-java-locale base-java-ui jakarta-commons-collections-tomcat5 jakarta-commons-dbcp-tomcat5 jakarta-commons-pool-tomcat5 tomcat6 tomcat6-admin-webapps tomcat6-docs-webapp tomcat6-el-2.1-api tomcat6-javadoc tomcat6-jsp-2.1-api tomcat6-lib tomcat6-servlet-2.5-api tomcat6-webapps php-process
OBSOLETES= mod_jk mod_jk-ap20

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
