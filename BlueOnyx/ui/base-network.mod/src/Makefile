ifdef TOPDIR
include $(TOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

.PHONY: base-network-am-rpm

all:
	make -C base-network-am all

install:
	make -C base-network-am install

rpm: base-network-am-rpm

base-network-am-rpm:
	tar -zcvf /tmp/base-network-am.tar.gz base-network-am &> /dev/null
	$(RPMBUILD) -ta /tmp/base-network-am.tar.gz &> /tmp/rpm.log
	/bin/rm -f /tmp/base-network-am.tar.gz
