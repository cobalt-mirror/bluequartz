ifndef CCEBASE
CCEBASE = /usr/sausalito
endif

include $(CCEBASE)/devel/defines.mk

SCRIPTS=logtail/adm_a.js logtail/adm_a.php logtail/adm_a.pl logtail/adm_e.js logtail/adm_e.php logtail/adm_e.pl logtail/ajax.js logtail/cron.js logtail/cron.php logtail/cron.pl logtail/index.html logtail/logtail.js logtail/maillog.js logtail/maillog.php logtail/maillog.pl logtail/messages.js logtail/messages.php logtail/messages.pl logtail/pa.js logtail/pa.php logtail/pa.pl logtail/pe.js logtail/pe.php logtail/pe.pl logtail/secure.js logtail/secure.php logtail/secure.pl

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
rpm:
