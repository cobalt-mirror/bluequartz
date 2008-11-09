#
# $Id: Makefile,v 1.33.2.6 2002/03/26 10:33:53 build Exp $
#

include /usr/sausalito/devel/defines.mk

VERSION=1.1
RELEASE=24

ROOT=$(PREFIX)
BACKUPDIR = $(ROOT)/var/cobalt/backups
BINDIR = $(ROOT)/usr/local/sbin
RCDIR = $(ROOT)/etc/rc.d
INITDIR = $(RCDIR)/init.d
PERLDIR = $(ROOT)/usr/sausalito/perl

INSTALL = install
CHMOD   = chmod
CHOWN   = chown

INITSCRIPT = cobalt_restore
SCRIPTS	   = cce_dump cce_restore cobalt_restorequotas \
	     cobalt_prebackup cobalt_postbackup cobalt_restoreowners

install: install-scripts install-init-script 

all:
	@echo Nothing to do.

clean:
	rm -rf rpms

install-init-script: $(INITSCRIPT)
	$(INSTALL) $(INSTALL_BINFLAGS) $(INITSCRIPT) $(INITDIR)

install-scripts: $(SCRIPTS)
	$(INSTALL) -d $(BINDIR)
	$(INSTALL) -d $(BACKUPDIR)
	$(INSTALL) -d $(INITDIR)
	$(INSTALL) $(INSTALL_BINFLAGS) $(SCRIPTS) $(BINDIR)

update_version:
	sed -e 's/XX_VER_XX/$(VERSION)/' \
	    -e 's/XX_REL_XX/$(RELEASE)/' scripts.spec.in > scripts.spec

rpm: clean update_version
	touch /tmp/time.stamp
	cd ..; tar -zcvf scripts.tar.gz monterey-backup.mod 2> /dev/null;\
	cp scripts.tar.gz /usr/src/redhat/SOURCES;\
	cp monterey-backup.mod/scripts.spec /usr/src/redhat/SPECS;\
	rpm -ba /usr/src/redhat/SPECS/scripts.spec;
	mkdir -p rpms; cp `find /usr/src/redhat/RPMS -type f -newer /tmp/time.stamp` rpms/.
	find rpms/ -name '*.rpm' -printf "RPM: %P\n" > packing_list

rpm_upload:
	(cd rpms; $(CCEBINDIR)/ftp_put * -d $(RPM_DIR))

