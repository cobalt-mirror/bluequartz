# Makefile

VENDOR=base
VENDORNAME='Project BlueOnyx'
SERVICE=maillist

# MAJVERSION is 0 until alpha release.
VERSION=1.4.1
RELEASE=1BQ5

INSTALL=install


BUILDARCH=noarch

BUILDUI=yes
BUILDGLUE=yes
BUILDLOCALE=yes
#BUILDSRC=yes

REQUIRES=majordomo
REQUIRES_UI=base-admserv-glue

include /usr/sausalito/devel/module.mk



install:
	@if [ -d /etc/admserv/conf/ ]; then \
		if [ x"`grep \"alias /base/maillist/archives /home/mhonarc/data\" /etc/admserv/conf/httpd.conf`" = x"" ]; then \
			echo "alias /base/maillist/archives /home/mhonarc/data" >> /etc/admserv/conf/httpd.conf; fi; \
	else \
		echo "WARNING:  /etc/admserv/conf/ is missing.  This may or may not be a problem."; \
	fi;

version:
	-@echo Version: $(VERSION)
	-@echo MAJVERSION: $(MAJVERSION)
	-@echo CVSVERSION: $(CVSVERSION)


foo:
	echo $(LOCALES)
