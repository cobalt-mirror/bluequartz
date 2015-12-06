ifndef CCETOPDIR
CCETOPDIR = /usr/sausalito
endif
 
include $(CCETOPDIR)/devel/defines.mk

.PHONY: blueonyx-letsencrypt-rpm

all: blueonyx-letsencrypt-rpm

install: blueonyx-letsencrypt-rpm

rpm: blueonyx-letsencrypt-rpm

blueonyx-letsencrypt-rpm:
	tar --exclude='.svn' -zcvf /tmp/blueonyx-letsencrypt.tar.gz blueonyx-letsencrypt &> /dev/null
	$(RPMBUILD) -ta /tmp/blueonyx-letsencrypt.tar.gz &> /tmp/rpm.log
	/bin/rm -f /tmp/blueonyx-letsencrypt.tar.gz

