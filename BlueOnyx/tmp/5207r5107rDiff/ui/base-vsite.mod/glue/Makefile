ifndef CCEBASE
CCEBASE = /usr/sausalito
endif

include $(CCEBASE)/devel/defines.mk

SCRIPTS=sbin/vsite_destroy.pl sbin/fix_user_suspension.pl sbin/web_alias_redirects.pl sbin/suPHP_fixer.pl sbin/phpsendmail sbin/php_settings_convert.pl
CONFIGS=php/set_php_headers.php

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
	mkdir -p $(CCESBINDIR)
	for file in $(SCRIPTS); do \
		$(INSTALL) $(INSTALL_BINFLAGS) $$file $(CCESBINDIR); \
	done
	mkdir -p $(PREFIX)/$(CCEBASE)/configs/php
	for file in $(CONFIGS); do \
		$(INSTALL) $(INSTALL_BINFLAGS) $$file $(PREFIX)/$(CCEBASE)/configs/php/; \
	done
rpm:
