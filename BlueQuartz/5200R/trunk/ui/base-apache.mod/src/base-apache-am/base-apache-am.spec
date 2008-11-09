Summary: Active Monitor support for base-apache-am
Name: base-apache-am
Version: 1.0.2
Release: 4BQ9%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
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

