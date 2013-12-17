# $Id: Makefile,v 1.3 2001/10/19 05:22:08 jcheng Exp $

ifdef TOPDIR
include $(TOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

CONFIG = drives_monterey.conf drives_alpine.conf fans_monterey.conf fans_alpine.conf ecc_alpine.conf

all:
install: 
	mkdir -p $(SWATCHDIR)
	install -m 644 $(CONFIG) $(SWATCHDIR)

rpm: 
	tar --exclude CVS -C ../ -czvf /tmp/base-alpine-hardware-conf.tar.gz base-alpine-hardware-conf > /dev/null 2>&1 && \
	$(RPMBUILD) -ta /tmp/base-alpine-hardware-conf.tar.gz > /tmp/rpm.log 2>&1 && \
	rm -rf /tmp/base-alpine-hardware-conf.tar.gz && \
	rm -rf /tmp/base-alpine-hardware-conf
