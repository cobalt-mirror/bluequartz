ifndef CCEBASE
CCEBASE = /usr/sausalito
endif

include $(CCEBASE)/devel/defines.mk

SCRIPTS=
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
rpm:
