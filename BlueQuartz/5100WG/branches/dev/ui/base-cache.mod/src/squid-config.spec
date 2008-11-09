Summary: Startup and configuration files for Squid
Name: squid-config
Version: 5.0.1
Release: 5
Requires: squid = 2.4.STABLE6
Copyright: Cobalt Networks 1999-2000
Group: Networking
Source: squid-config.tar.gz
BuildRoot: /tmp/squid-config
BuildArchitectures: noarch

%changelog
* Tue Oct 29 2002 Dennis Tiu <dennis.tiu@sun.com>
	- now using squid v2.4
	- post rpm install reflects locattion of squid bin

* Thu Oct 10 2002 Dennis Tiu <dennis.tiu@sun.com>
	- defining safe ports and ssl ports to be used
		for http_access
	- uses squid v2.3

* Fri Jun 02 2000 Patrick Bose <pbose@cobalt.com>
	- rel 5.0-1 based on squid-config-minime 4.0-8
	- based on Squid 2.3 Stable 3

%description
Startup and config files/scripts for Squid running on CacheQube and
derivatives.  This rpm should be kept in sync with the spec file
on the squid rpm to ensure that one of the two rpms contains all
the required files and that paths are correct.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -n squidconfig

%build

%install
make PREFIX=$RPM_BUILD_ROOT install

%post
#su squid -c "/home/squid2/bin/squid -z -f /etc/squid/squid.conf"
su squid -c "/usr/sbin/squid -z -f /etc/squid/squid.conf"

%clean
rm -rf $RPM_BUILD_ROOT

%files
/etc/squid
#/usr/bin/check-squid
#/usr/bin/http-redirect-on
#/usr/bin/http-redirect-off
#/usr/bin/do-http-redirect
#/etc/cron.daily/cache-nightly
/etc/rc.d/init.d/squid

