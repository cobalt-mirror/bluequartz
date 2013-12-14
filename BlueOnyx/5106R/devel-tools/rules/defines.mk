CCEBASE=/usr/sausalito
CCEDIR=$(PREFIX)$(CCEBASE)
CCEWEB=$(CCEBASE)/ui/web

CCELIBDIR=$(CCEDIR)/lib
CCEINCDIR=$(CCEDIR)/include
CCEBINDIR=$(CCEDIR)/bin
CCESBINDIR=$(CCEDIR)/sbin
CCEPERLDIR=$(CCEDIR)/perl
CCEPHPLIBDIR=$(PREFIX)$(shell php-config --extension-dir)
CCEPHPCLASSDIR=$(CCEDIR)/ui/libPhp
CCETMPDIR=$(PREFIX)/tmp
CCECONFDIR=$(CCEDIR)/conf
CCESCHEMASDIR=$(CCEDIR)/schemas
CCESESSIONSDIR=$(CCEDIR)/sessions
CCEHANDLERDIR=$(CCEDIR)/handlers
CCEWRAPD=$(PREFIX)/etc/ccewrap.d

CCELOCALEDIR=$(PREFIX)/usr/share/locale/

CCECDEFS=-I$(CCEINCDIR) -D_REENTRANT
CCESHAREDCDEFS=$(CCECDEFS) -fPIC
CCELIBDEFS=-L$(CCELIBDIR)
CCESHAREDLDDEFS=-shared -rdynamic

SWATCHDIR=$(PREFIX)/usr/sausalito/swatch
SWATCHCONFDIR=$(SWATCHDIR)/conf
SWATCHBINDIR=$(SWATCHDIR)/bin

# lcd installation
LCDINSTALLDIR=$(PREFIX)/etc/lcd.d/10main.m
LCDDIR=/etc/lcd.d/10main.m

# default permissions
INSTALL_DEFAULT=-o root -g root
INSTALL_HEADERFLAGS=$(INSTALL_DEFAULT) -m 644
INSTALL_BINFLAGS=$(INSTALL_DEFAULT) -m 755
INSTALL_SBINFLAGS=$(INSTALL_DEFAULT) -m 700
INSTALL_SCRIPTFLAGS=$(INSTALL_DEFAULT) -m 755
INSTALL_LIBFLAGS=$(INSTALL_DEFAULT) -m 644
INSTALL_SHLIBFLAGS=$(INSTALL_DEFAULT) -m 755

# non-cce stuff
RANLIB=ranlib
AR=ar
CC=gcc
INSTALL=install
RPM_DIR=/fargo/rpms/i386
SRPM_DIR=/fargo/srpms
PACKAGE_DIR=$(shell if [ -d /usr/src/redhat ]; then \
			echo /usr/src/redhat; \
		elif [ -d /var/src/rpm ]; then \
			echo /var/src/rpm; \
		elif [ -d /usr/src/packages ]; then \
			echo /usr/src/packages; \
        elif [ -d /root/rpmbuild ]; then \ 
            echo /root/rpmbuild; \ 
		fi)
RPM_TOPDIR=$(shell rpm --eval='%{_topdir}')

XLOCALEPAT=de es fr zh_CN zh_TW
RPMBUILD=$(shell which rpmbuild>/dev/null 2>&1&&echo rpmbuild||echo rpmbuild)
USER_HTTPD=$(shell id httpd>/dev/null 2>&1&&echo httpd||echo apache)

