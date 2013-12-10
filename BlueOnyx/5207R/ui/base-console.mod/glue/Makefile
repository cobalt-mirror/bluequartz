ifndef CCEBASE
CCEBASE = /usr/sausalito
endif

include $(CCEBASE)/devel/defines.mk

SCRIPTS=logtail/adm_a.js logtail/adm_a.php logtail/adm_e.js logtail/adm_e.php logtail/ajax.js logtail/cron.js logtail/cron.php logtail/index.html logtail/logtail.js logtail/maillog.js logtail/maillog.php logtail/messages.js logtail/messages.php logtail/pa.js logtail/pa.php logtail/pe.js logtail/pe.php logtail/secure.js logtail/secure.php
CRONS=cron.d/pam_abl_purge.cron
SBIN=sbin/pam_abl_import.pl
PAM=pam.d/password-auth-ac.5107R pam.d/system-auth.5106R
SUIDSCRIPTS=slogtail/adm_a.pl slogtail/adm_e.pl slogtail/cron.pl slogtail/maillog.pl slogtail/messages.pl slogtail/pa.pl slogtail/pe.pl slogtail/secure.pl
all:

install:
	skel_files=`find etc -type f -not -path '*CVS*' -not -path '*/.*'`; \
	for file in $$skel_files; do \
		dir=`dirname $$file`; \
		if [ ! -d "$(PREFIX)/$$dir" ]; then \
			$(INSTALL) $(INSTALL_DEFAULT) -d $(PREFIX)/$$dir; \
		fi; \
		$(INSTALL) $(INSTALL_HEADERFLAGS) $$file $(PREFIX)/$$dir; \
	done
	mkdir -p $(PREFIX)/$(CCEWEB)/base/console/.logtail
	for file in $(SCRIPTS); do \
		$(INSTALL) $(INSTALL_DEFAULT) $$file $(PREFIX)/$(CCEWEB)/base/console/.logtail/; \
	done
	mkdir -p $(PREFIX)/etc/cron.d
	for file in $(CRONS); do \
		$(INSTALL) $(INSTALL_DEFAULT) $$file $(PREFIX)/etc/cron.d/; \
	done
	mkdir -p $(PREFIX)/usr/sausalito/sbin
	for file in $(SBIN); do \
		$(INSTALL) $(INSTALL_DEFAULT) $$file $(PREFIX)/usr/sausalito/sbin/; \
	done
	mkdir -p $(PREFIX)/etc/pam.d
	for file in $(PAM); do \
		$(INSTALL) $(INSTALL_DEFAULT) $$file $(PREFIX)/etc/pam.d/; \
	done
	mkdir -p $(PREFIX)/usr/sausalito/bin/logtail
	for file in $(SUIDSCRIPTS); do \
		$(INSTALL) $(INSTALL_DEFAULT) $$file $(PREFIX)/usr/sausalito/bin/logtail/; \
	done

rpm:
