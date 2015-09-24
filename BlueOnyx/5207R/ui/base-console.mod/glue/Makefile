ifndef CCEBASE
CCEBASE = /usr/sausalito
endif

include $(CCEBASE)/devel/defines.mk

CRONS=cron.daily/pam_abl.sh
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
	mkdir -p $(PREFIX)/etc/cron.daily
	for file in $(CRONS); do \
		$(INSTALL) $(INSTALL_DEFAULT) $$file $(PREFIX)/etc/cron.daily/; \
	done
	mkdir -p $(PREFIX)/usr/sausalito/configs
	pam_files=`find pam.d -type f -not -path '*CVS*' -not -path '*/.*'`; \
	for file in $$pam_files; do \
		dir=`dirname $$file`; \
		if [ ! -d "$(PREFIX)/usr/sausalito/configs/$$dir" ]; then \
			$(INSTALL) $(INSTALL_DEFAULT) -d $(PREFIX)/usr/sausalito/configs/$$dir; \
		fi; \
		$(INSTALL) $(INSTALL_HEADERFLAGS) $$file $(PREFIX)/usr/sausalito/configs/$$dir; \
	done
rpm:
