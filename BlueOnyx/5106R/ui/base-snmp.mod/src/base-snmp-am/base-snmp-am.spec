Summary: Scripts used to integrate SNMP into ActiveMonitor
Name: base-snmp-am
Version: 1.0.2
Release: 1BX01%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-snmp-am.tar.gz
BuildRoot: /tmp/base-snmp-am
Requires: net-snmp-utils

%prep
%setup -n base-snmp-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
The scripts necessary to check the current status of the SNMP daemon.  
This is called by swatch+cce as part of the ActiveMonitor subsystem.

%changelog
* Mon Apr 18 2011 Michael Stauber <mstauber@solarspeed.net> 1.0.2-1BX01
- Removed strict from script, adjusted paths.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 1.0.1-5BQ9
- Rebuilt for BlueOnyx.

* Sun Feb 03 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.1-5BQ8
- add sign to the package.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-5BQ7
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-5BQ6
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-5BQ5
- remove Serial tag.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-5BQ4
- clean up spec file.

* Sun Nov 21 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-5BQ3
- add Requires: net-snmp-utils

* Sun Nov 21 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-5BQ2
- fix am_snmp.pl for net-snmp

* Wed Jan 14 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-5BQ1
- build for Blue Quartz

* Wed Jun 13 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file

