ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif
 
include $(CCETOPDIR)/devel/defines.mk

.PHONY: istatd-rpm

all:
	make -C istatd all

install:
	make -C istatd install

rpm: istatd-rpm

istatd-rpm:
	tar --exclude='.svn' -zcvf /tmp/istatd.tar.gz istatd &> /dev/null
	$(RPMBUILD) -ta /tmp/istatd.tar.gz &> /tmp/rpm.log
	/bin/rm -f /tmp/istatd.tar.gz
