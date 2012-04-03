ifndef CCEBASE
CCEBASE = /usr/sausalito
endif

include $(CCEBASE)/devel/defines.mk

CONF=phpsysinfo/config.php phpsysinfo/config.php.5106R
all:

install:
	mkdir -p $(PREFIX)/$(CCEWEB)/base/phpsysinfo/.phpsysinfo
	for file in $(CONF); do \
		$(INSTALL) $(INSTALL_DEFAULT) $$file $(PREFIX)/$(CCEWEB)/base/phpsysinfo/.phpsysinfo/; \
	done
rpm:
