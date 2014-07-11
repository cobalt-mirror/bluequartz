# Makefile

VENDOR=base
VENDORNAME='Project BlueOnyx'
SERVICE=mailman

# MAJVERSION
VERSION=2.0.0
RELEASE=0BX02

INSTALL=install

BUILDARCH=noarch

BUILDUI=new
BUILDGLUE=yes
BUILDLOCALE=yes
BUILDSRC=yes

REQUIRES=mailman base-blueonyx-capstone base-blueonyx-glue base-mailman-am
#OBSOLETES=majordomo base-maillist-capstone base-maillist-ui base-maillist-glue 

include /usr/sausalito/devel/module.mk


install:
	@if [ -d /etc/admserv/conf/ ]; then \
		if [ x"`grep \"alias /base/mailman/archives /home/mhonarc/data\" /etc/admserv/conf/httpd.conf`" = x"" ]; then \
			echo "alias /base/mailman/archives /home/mhonarc/data" >> /etc/admserv/conf/httpd.conf; fi; \
	else \
		echo "WARNING:  /etc/admserv/conf/ is missing.  This may or may not be a problem."; \
	fi;

version:
	-@echo Version: $(VERSION)
	-@echo MAJVERSION: $(MAJVERSION)
	-@echo CVSVERSION: $(CVSVERSION)


foo:
	echo $(LOCALES)
