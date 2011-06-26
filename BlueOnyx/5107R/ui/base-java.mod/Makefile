# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Team BlueOnyx'
SERVICE=java

VERSION=3.0.0
RELEASE=1

# add a buildarch if desired
#BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=yes

REQUIRES= base-java-am tomcat6-admin-webapps.noarch tomcat6-docs-webapp.noarch tomcat6-javadoc.noarch tomcat6-jsp-2.1-api.noarch tomcat6-lib.noarch tomcat6-servlet-2.5-api.noarch tomcat6-webapps.noarch jakarta-commons-collections-tomcat5.noarch tomcat6.noarch tomcat6-log4j.noarch jakarta-commons-dbcp-tomcat5.noarch jakarta-commons-pool-tomcat5.i686 tomcat6-el-2.1-api.noarch apache-jasper.noarch apache-jasper-javadoc.noarch apache-tomcat-apis.noarch
OBSOLETES= mod_jk mod_jk-ap20

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
