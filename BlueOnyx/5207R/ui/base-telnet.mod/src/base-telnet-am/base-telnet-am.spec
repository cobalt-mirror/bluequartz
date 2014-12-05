Summary: Active Monitor support for base-telnet-am
Name: base-telnet-am
Version: 1.0.3
Release: 1BX03%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-telnet-am.tar.gz
BuildRoot: /tmp/base-telnet-am

%prep
%setup -n base-telnet-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-telnet-am.  

%changelog

* Thu Dec 04 2013 Michael Stauber <mstauber@solarspeed.net> 1.0.3-1BX03
- Systemd related fixes.

* Sun Dec 08 2013 Michael Stauber <mstauber@solarspeed.net> 1.0.3-1BX02
- Removed .svn directory from package.

* Tue Jun 08 2010 Michael Stauber <mstauber@solarspeed.net> 1.0.3-1BX01
- We telnet to 127.0.0.1 instead of localhost now

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 1.0.2-4BQ8
- Rebuilt for BlueOnyx

* Sun Feb 03 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.2-4BQ7
- add dist macro for release.

* Sun Feb 03 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.2-4BQ6
- add sign to the package.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ5
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ4
- remove Serial tag.

* Mon Aug 15 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ3
- clean up spec file.

* Mon Aug 23 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ2
- restart xinetd instead of inetd.

* Mon Jan 12 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ1
- build for Blue Quartz

* Thu Jun 15 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file, add expect style tests

