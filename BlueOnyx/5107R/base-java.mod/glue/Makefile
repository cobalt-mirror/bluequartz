ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif

# include defines.mk here before checking to see which target make is doing
include $(CCETOPDIR)/devel/defines.mk

# check PREFIX, because if it is defined we are 'make rpm' and CCETOPDIR
# needs to be redefined appropriately
ifdef PREFIX
CCETOPDIR = $(PREFIX)/usr/sausalito
endif

PROFILEDIR = $(PREFIX)/etc/profile.d
PROFILES=java.sh

MODJKDIR = $(PREFIX)/etc/httpd/conf.d
MODJK=mod_jk.conf

WORKERSDIR = $(PREFIX)/etc/tomcat6/jk
WORKERS=workers.properties

USERSDIR = $(PREFIX)/etc/tomcat6
USERS=tomcat-users.xml.dist tomcat6.logrotate.dist

MODULES = $(PREFIX)/usr/lib/httpd/modules

# Use the right mod_jk.so based on the architecture
MODULEJK=$(shell if [ `uname -m` = "x86_64" ]; then  echo "64-bit.el6/mod_jk.so"; else  echo "32-bit.el6/mod_jk.so"; fi) 
#MODULEJK=mod_jk.so

SCRIPTS=java_load_war.pl

all:

install:
	echo "making dir:"
	echo $(CCESBINDIR)
	mkdir -p $(CCESBINDIR)
	mkdir -p $(PROFILEDIR)
	mkdir -p $(MODJKDIR)
	mkdir -p $(WORKERSDIR)
	mkdir -p $(USERSDIR)
	mkdir -p $(MODULES)
	(cd sbin; $(INSTALL) $(INSTALL_BINFLAGS) $(SCRIPTS) $(CCESBINDIR); )
	(cd etc/profile.d; $(INSTALL) $(PROFILES) $(PROFILEDIR); )
	(cd etc/httpd/conf.d; $(INSTALL) $(MODJK) $(MODJKDIR); )
	(cd etc/tomcat6/jk; $(INSTALL) $(WORKERS) $(WORKERSDIR); )
	(cd etc/tomcat6; $(INSTALL) $(USERS) $(USERSDIR); )
	(cd modules; $(INSTALL) $(INSTALL_BINFLAGS) $(MODULEJK) $(MODULES); )

rpm:

