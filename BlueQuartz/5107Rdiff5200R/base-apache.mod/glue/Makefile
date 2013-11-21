# $Id: Makefile 1538 2010-10-13 09:46:37Z oride $
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

install: install_crond install_sbin install_etc install_httpdconf

install_etc: 
	mkdir -p $(PREFIX)/etc/
	install -m 0600 -o root etc/suexec.conf $(PREFIX)/etc/

install_crond:
	mkdir -p $(PREFIX)/etc/cron.d/
	install -m 0600 -o root etc/cron.d/suphp_session_file_clear.cron $(PREFIX)/etc/cron.d/

install_sbin:
	mkdir -p $(PREFIX)/usr/local/sbin/
	install -m 0750 -o root usr/local/sbin/suphp_session_file_clear.sh $(PREFIX)/usr/local/sbin/

