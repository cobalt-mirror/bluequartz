Summary: Binaries and scripts used by Active Monitor for base-network
Name: base-network-am
Version: 1.0.2
Release: 1%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-network-am.tar.gz
BuildRoot: /tmp/%{name}

%prep
%setup -n %{name}

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains a number of binaries and scripts used by the Active
Monitor subsystem to monitor services provided by the base-network module.  

%changelog

* Tue Dec 03 2013 Michael Stauber <mstauber@solarspeed.net> 1.0.2-1
- Removed .svn directory from rpm package.

* Sat Apr 10 2010 Michael Stauber <mstauber@solarspeed.net> 1.0.1-3BQ9
- Fixed /usr/sausalito/swatch/bin/am_network.sh for non VPS'ed installs.

* Sun Apr 04 2010 Michael Stauber <mstauber@solarspeed.net> 1.0.1-3BQ8
- Added better OpenVZ support as suggested by Greg Kuhnert:
- If we're a VPS, then am_network.sh cannot ping the Gateway. So we ping the masternode instead.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 1.0.1-3BQ7
- Rebuilt for BlueOnyx.

* Sun Feb 03 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.1-3BQ6
- add sign to the package.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ5
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ4
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ3
- remove Serial tag.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ2
- clean up spec file.

* Wed Jan 14 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ1
- build for Blue Quartz

* Wed Aug 30 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

