# $Id: Makefile 352 2004-05-28 02:10:53Z shibuya $
#
# Makefile to install handlers.  Right now, it's a bit perl-specific,
# but it can be easily generalized.
#
# supported targets:
#		all -- does nothing
#		install -- installs everything
#		install_links -- creates links instead of copies of files (for dbging)
#		rpm -- rolls an rpm for this stuff

SCRIPTS=sbin/fix_302_httpd_redirects.pl sbin/fix_web-and-email-server-aliasses.pl sbin/set_default_php_settings.pl sbin/toggle_ssl.pl sbin/SSL_fixer.pl

ifdef CCETOPDIR
include $(CCETOPDIR)/devel/defines.mk
else
include /usr/sausalito/devel/defines.mk
endif

INSTALL = /usr/bin/install -m 0700 -o root 
PWD = $(shell pwd)

install: install_etc install_httpdconf install_sbin

install_etc: 
	mkdir -p $(PREFIX)/etc/ccewrap.d/base/apache
	install -m 0600 -o root etc/suexec.conf $(PREFIX)/etc/
	install -m 0644 -o root ccewrap/apache.xml $(PREFIX)/etc/ccewrap.d/base/apache/apache.xml

install_httpdconf:
	mkdir -p $(PREFIX)/etc/httpd/conf.d
	mkdir -p $(PREFIX)/etc/httpd/conf.perl 
	#install -o root -g root -m 644 httpd/ssl_perl.conf $(PREFIX)/etc/httpd/conf.d
	install -o root -g root -m 644 httpd/security.conf $(PREFIX)/etc/httpd/conf.d
	install -o root -g root -m 644 httpd/ssl_bx.conf $(PREFIX)/etc/httpd/conf.d
	install -o root -g root -m 755 httpd/00-default-vsite.pl $(PREFIX)/etc/httpd/conf.perl/00-default-vsite.pl 

install_sbin:
	$(INSTALL) -d -o root -m 0755 $(PREFIX)/usr/sausalito/sbin; \
	$(INSTALL) -o root -m 0755 $(SCRIPTS) $(PREFIX)//usr/sausalito/sbin/; \
