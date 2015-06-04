Summary: Active Monitor support for base-apache-am
Name: base-apache-am
Version: 1.0.6
Release: 0BX05%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-apache-am.tar.gz
BuildRoot: /tmp/base-apache-am

%prep
%setup -n base-apache-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-apache-am.  

%changelog

* Wed Jun 03 2015 Michael Stauber <mstauber@solarspeed.net> 1.0.6-0BX05
- Fixes in src/base-apache-am/am_apache.pl

* Wed Jun 03 2015 Michael Stauber <mstauber@solarspeed.net> 1.0.6-0BX04
- Fixes in src/base-apache-am/am_apache.pl

* Wed Jun 03 2015 Michael Stauber <mstauber@solarspeed.net> 1.0.6-0BX03
- Modified src/base-apache-am/am_apache.sh to run am_apache.pl as well.
- Added src/base-apache-am/am_apache.pl to check if httpd processes
  have detached from the master and kill them before am_apache.exp runs.

* Mon Mar 16 2015 Michael Stauber <mstauber@solarspeed.net> 1.0.6-0BX02
- Small fix to AM scripts for the extra PHP versions. If no extra PHP is
  present, we exit with noInfo instead.

* Fri Mar 13 2015 Michael Stauber <mstauber@solarspeed.net> 1.0.6-0BX01
- Added monitoring for PHP-FPM of extra PHP versions.

* Sat Jan 31 2015 Michael Stauber <mstauber@solarspeed.net> 1.0.5-0BX01
- Added Monitor for PHP-FPM.

* Mon Dec 08 2014 Michael Stauber <mstauber@solarspeed.net> 1.0.4-0BX02
- Systemd love added.

* Thu Dec 12 2013 Michael Stauber <mstauber@solarspeed.net> 1.0.4-0BX01
- As we added theability to change the default HTTP port, it is necessary
  to modify the Active Monitor component that checks if HTTP is working.
- Added am_apache.sh to call a modified am_apache.exp with the correct
  port information as argument. The port information is obtained by
  parsing httpd.conf for the correct Apache port based on the ^Listen
  parameter.  

* Wed Dec 04 2013 Michael Stauber <mstauber@solarspeed.net> 1.0.3-0BX01
- Remove .svn directory from rpm package.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 1.0.2-4BQ11
- Rebuilt for BlueOnyx

* Sun Feb 03 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.2-4BQ10
- add sign to the package.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ9
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ8
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ7
- remove Serial tag.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpah.or.jp> 1.0.2-4BQ6
- add serial number.

* Thu Aug 11 2005 Hisao SHIBUYA <shibuya@alpah.or.jp> 1.0.2-4BQ5
- clean up spec file.

* Thu Aug 11 2005 Hisao SHIBUYA <shibuya@alpah.or.jp> 1.0.2-4BQ4
- clean up spec file.

* Fri May 28 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ3
- fixed http result

* Tue Apr 20 2004 Anders <andersb@blacksun.ca> 1.0.2-4BQ2
- fixed invalid requests

* Tue Jan 08 2004 Hisao SHIBUYA Mshibuya@alpha.or.jp> 1.0.2-4BQ1
- build for Blue Quartz

* Wed Jan 23 2002 James Cheng <james.y.cheng@sun.com>
- fix to check port 444 for admserv, not port 81
* Thu Jun 14 2001 James Cheng <james.y.cheng@sun.com>
- add expect style tests
* Wed Jun 28 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

