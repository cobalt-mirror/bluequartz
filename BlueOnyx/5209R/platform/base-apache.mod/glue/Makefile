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

install: install_etc install_httpdconf install_sbin install_fpm

install_etc: 
	mkdir -p $(PREFIX)/etc/ccewrap.d/base/apache
	install -m 0600 -o root etc/suexec.conf $(PREFIX)/etc/
	install -m 0644 -o root ccewrap/apache.xml $(PREFIX)/etc/ccewrap.d/base/apache/apache.xml

install_httpdconf:
	mkdir -p $(PREFIX)/etc/httpd/conf.d
	#install -o root -g root -m 644 httpd/ssl_perl.conf $(PREFIX)/etc/httpd/conf.d
	install -o root -g root -m 644 httpd/security.conf $(PREFIX)/etc/httpd/conf.d
	install -o root -g root -m 644 httpd/ssl_bx.conf $(PREFIX)/etc/httpd/conf.d

install_sbin:
	$(INSTALL) -d -o root -m 0755 $(PREFIX)/usr/sausalito/sbin; \
	$(INSTALL) -o root -m 0755 $(SCRIPTS) $(PREFIX)/usr/sausalito/sbin/; \

install_fpm:
	mkdir -p $(PREFIX)/usr/lib/systemd/system/
	mkdir -p $(PREFIX)/usr/sausalito/configs/systemd/
	install -o root -g root -m 644 php-fpm/php-fpm.service $(PREFIX)/usr/sausalito/configs/systemd/
	install -o root -g root -m 644 php-fpm/php-fpm-5.3.service $(PREFIX)/usr/lib/systemd/system/
	install -o root -g root -m 644 php-fpm/php-fpm-5.4.service $(PREFIX)/usr/lib/systemd/system/
	install -o root -g root -m 644 php-fpm/php-fpm-5.5.service $(PREFIX)/usr/lib/systemd/system/
	install -o root -g root -m 644 php-fpm/php-fpm-5.6.service $(PREFIX)/usr/lib/systemd/system/
	mkdir -p $(PREFIX)/etc/php-fpm.d
	mkdir -p $(PREFIX)/etc/php-fpm-5.3.d
	mkdir -p $(PREFIX)/etc/php-fpm-5.4.d
	mkdir -p $(PREFIX)/etc/php-fpm-5.5.d
	mkdir -p $(PREFIX)/etc/php-fpm-5.6.d
	install -o root -g root -m 644 php-fpm/etc/php-fpm.d/www.conf $(PREFIX)/etc/php-fpm.d/www.conf
	install -o root -g root -m 644 php-fpm/etc/php-fpm-5.3.d/www.conf $(PREFIX)/etc/php-fpm-5.3.d/www.conf
	install -o root -g root -m 644 php-fpm/etc/php-fpm-5.4.d/www.conf $(PREFIX)/etc/php-fpm-5.4.d/www.conf
	install -o root -g root -m 644 php-fpm/etc/php-fpm-5.5.d/www.conf $(PREFIX)/etc/php-fpm-5.5.d/www.conf
	install -o root -g root -m 644 php-fpm/etc/php-fpm-5.6.d/www.conf $(PREFIX)/etc/php-fpm-5.6.d/www.conf
