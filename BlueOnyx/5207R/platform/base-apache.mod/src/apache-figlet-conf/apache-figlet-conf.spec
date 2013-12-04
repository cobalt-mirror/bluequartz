Summary: apache-conf: the figlet approach
Name: apache-figlet-conf
Version: 2.0.0
Release: 0BX01
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: apache-figlet-conf.tar.gz
BuildArchitectures: noarch
BuildRoot: /var/tmp/apachefigconf

%prep
%setup -n apache-figlet-conf

%build
make all

%install
rm -rf $RPM_BUILD_ROOT
PREFIX=$RPM_BUILD_ROOT make install

%files
/etc/httpd/conf/conf_assemble
%config/etc/httpd/conf/figlets
%config/etc/httpd/conf/httpd.conf
%config/etc/httpd/conf/access.conf
%config/etc/httpd/conf/srm.conf

%description
This package contains tools to build an apache config file from a
number of smaller parts.

%changelog

* Wed Dec 04 2013 Michael Stauber <mstauber@solarspeed.net> 2.0.0-0BX01
- Remove .svn directory from rpm package.

* Wed Aug 02 2000 Patrick Baltz <pbaltz@cobalt.com>
- redirect http://server/login/ to http://server:444/login.php

* Fri Jul 28 2000 Patrick Baltz <pbaltz@cobalt.com>
- build apache-figlet-conf in a temp directory for make rpm so stuff in
- figlets directory on build machine doesn't get picked up accidently

* Thu Jul 27 2000 Will DeHaan <will@cobalt.com>
- de-figlet core httpd.conf and srm.conf data

* Sun Apr 29 2000 Jonathan Mayer <jmayer@cobalt.com>
- initial spec file

