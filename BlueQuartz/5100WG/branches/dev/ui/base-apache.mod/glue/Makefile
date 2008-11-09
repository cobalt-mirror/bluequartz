# $Id: Makefile 201 2003-07-18 19:11:07Z will $
#
# Makefile to install handlers.  Right now, it's a bit perl-specific,
# but it can be easily generalized.
#
# supported targets:
#		all -- does nothing
#		install -- installs everything
#		install_links -- creates links instead of copies of files (for dbging)
#		rpm -- rolls an rpm for this stuff

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

INSTALL = /usr/bin/install -m 0700 -o root 
PWD = $(shell pwd)

install: install_etc 

install_etc: 
	mkdir -p $(PREFIX)/etc/
	install -m 0600 -o root etc/suexec.conf $(PREFIX)/etc/
	cd /etc/httpd; ln -s ../../usr/lib/apache libexec
