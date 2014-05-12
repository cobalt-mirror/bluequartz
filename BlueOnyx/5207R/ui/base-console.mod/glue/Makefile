ifndef CCEBASE
CCEBASE = /usr/sausalito
endif

include $(CCEBASE)/devel/defines.mk

CRONS=cron.d/pam_abl_purge.cron
SBIN=sbin/pam_abl_import.pl
PAM=pam.d/password-auth-ac.5107R pam.d/system-auth.5106R
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

rpm:
