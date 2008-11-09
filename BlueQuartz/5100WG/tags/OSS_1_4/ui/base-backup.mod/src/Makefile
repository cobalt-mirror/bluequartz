#
# Makefile for creating LED and LCD utilities
#

.PHONY: backup-scripts-rpm

ROOT=$(PREFIX)
BINDIR = $(ROOT)/usr/local/sbin
CRONDAILY = $(ROOT)/etc/cron.daily
PERLDIR = $(ROOT)/usr/sausalito/perl
LOGROTATEDIR = $(ROOT)/etc/logrotate.d

INSTALL = install
CHMOD   = chmod
CHOWN   = chown

CRONJOB = restore.tmpwatch
PERLMOD = Backup.pm
SCRIPTS  = cbackup crestore multi-volume.pl backup_rm_hist.pl
LOGROTATE = backup

install: install-all

all:
	@echo Nothing to do.

install-all: $(SCRIPTS)
	$(INSTALL) -d $(BINDIR)
	$(INSTALL) -m 500 $(SCRIPTS) $(BINDIR)
	$(INSTALL) -d $(PERLDIR)
	$(INSTALL) -m 644 $(PERLMOD) $(PERLDIR)
	$(INSTALL) -d $(LOGROTATEDIR)
	$(INSTALL) -m 644 $(LOGROTATE) $(LOGROTATEDIR)
	$(INSTALL) -d $(CRONDAILY)
	$(INSTALL) -m 755 $(CRONJOB) $(CRONDAILY)

rpm: rpm-scripts-backup

rpm-scripts-backup:
	cd ..;tar -zcvf backup-scripts.tar.gz src &> /dev/null;\
	cp backup-scripts.tar.gz /usr/src/redhat/SOURCES;\
	cp src/backup-scripts.spec /usr/src/redhat/SPECS;\
	rpm -ba /usr/src/redhat/SPECS/backup-scripts.spec; \
	mkdir rpms; cp /usr/src/redhat/RPMS/i386/backup-scripts-1.01-1.i386.rpm rpms

