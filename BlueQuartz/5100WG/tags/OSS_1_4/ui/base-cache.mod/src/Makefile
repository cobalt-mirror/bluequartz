#
# Makefile for cache configuration files and special scripts
# such as init, cron, and cleanup
# $Id: Makefile 3 2003-07-17 15:19:15Z will $
#

all:	
	# Nothing to do

clean:
	# Nothing to do

install:
	# Make sure the squid user exists
	/usr/sbin/useradd -M -r -d /home/squid2 -s "" -c "Squid Cache" squid >/dev/null 2>&1 || :

	# squid.conf
	mkdir -p $(PREFIX)/etc/squid
	cp squid.conf                   $(PREFIX)/etc/squid

	# program(s):
#	mkdir -p $(PREFIX)/usr/bin
#	cp check-squid                  $(PREFIX)/usr/bin

	# cron job(s):
#	mkdir -p $(PREFIX)/etc/cron.daily
#	cp cache-nightly                $(PREFIX)/etc/cron.daily

	# init script(s):
	chmod 755 *.init
	mkdir -p                        $(PREFIX)/etc/rc.d/init.d
	cp squid.init                   $(PREFIX)/etc/rc.d/init.d/squid

	chown root.root $(PREFIX)/etc/rc.d/init.d/squid
	chown -R squid.squid $(PREFIX)/etc/squid
	
	# Don't create and include swap directories in an rpm
	if [ "$(PREFIX)" = "" ]; then \
		su squid -c "/home/squid2/bin/squid -z -f /etc/squid/squid.conf"; \
	fi

rpm: rpm-carmel

rpm-carmel:
	@echo " --> building squid-config rpm for carmel"
	cp squid-config.spec /usr/src/redhat/SPECS
	cp -R . /tmp/squidconfig
	tar --exclude CVS -C /tmp -czvf /usr/src/redhat/SOURCES/squid-config.tar.gz squidconfig
	rpm -ba /usr/src/redhat/SPECS/squid-config.spec


