ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif
 
include $(CCETOPDIR)/devel/defines.mk

.PHONY: blueonyx-gmu-rpm

all: blueonyx-gmu-rpm

install: blueonyx-gmu-rpm

rpm: blueonyx-gmu-rpm

blueonyx-gmu-rpm:
	tar --exclude='.svn' -zcvf /tmp/blueonyx-gmu.tar.gz blueonyx-gmu &> /dev/null
	$(RPMBUILD) -ta /tmp/blueonyx-gmu.tar.gz &> /tmp/rpm.log
	/bin/rm -f /tmp/blueonyx-gmu.tar.gz
