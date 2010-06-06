# specify the following variables:
VENDOR=base

# if the VENDOR field is an alias, this should be the real name.
# otherwise, set it to VENDOR.
VENDORNAME='Project BlueOnyx'
SERVICE=mysql

VERSION=2.0.1
RELEASE=22

# add a buildarch if desired
BUILDARCH=noarch

# locale exclude pattern
#XLOCALEPAT=

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=yes

REQUIRES= mysql-server mysql php-mysql
OBSOLETES= solarspeed-mysql-gui solarspeed-mod_mysql <= 1.0.5 nuonce-mysql-ui <= 1.0.0 nuonce-mysql-locale-en  <= 1.0.0 nuonce-mysql-glue <= 1.0.0 nuonce-mysql-locale-ja <= 1.0.0 nuonce-mysql-capstone <= 1.0.0 solarspeed-mod_mysql-glue <= 1.0.1 solarspeed-mod_mysql-capstone <= 1.0.1 solarspeed-mod_mysql-locale-en <= 1.0.1 solarspeed-mod_mysql-ui <= 1.0.1 solarspeed-mysql-locale-en <= 1.0.0 solarspeed-mysql-ui <= 1.0.0 solarspeed-mysql-capstone <= 1.0.0 solarspeed-mysql-glue <= 1.0.0 

#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

include /usr/sausalito/devel/module.mk
