# $Id: Makefile 259 2004-01-03 06:28:40Z shibuya $
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

INSTALL = /usr/bin/install -m 0755 -o root 

SBIN_SCRIPTS = ssl_get.pl ssl_import.pl letsencrypt_autorenew.pl LE_SSL_fixer.pl
CRON_DAILY = letsencrypt.cron

install: install_sbin install_crondaily install_httpdconf

install_etc: 
	tar -C etc . --exclude .svn -cf - | tar -C /etc -xf -

install_initd:
	tar -C init.d . --exclude .svn -cf - | tar -C /etc/rc.d/init.d -xf -

install_sbin:
	@mkdir -p $(CCEDIR)/sbin
	cd sbin; for file in $(SBIN_SCRIPTS); do \
		$(INSTALL) $$file $(CCEDIR)/sbin; \
	done

install_crondaily:
	@mkdir -p $(PREFIX)/etc/cron.daily
	cd etc/cron.daily; for file in $(CRON_DAILY); do \
		$(INSTALL) $$file $(PREFIX)/etc/cron.daily/; \
	done

# used during development: installs symlinks instead of code.
install_links:
	make install INSTALL="ln -sf "

install_httpdconf:
	mkdir -p $(PREFIX)/etc/httpd/conf.d
	install -o root -g root -m 644 httpd/acme_sh.conf $(PREFIX)/etc/httpd/conf.d

