Summary: Active Monitor support for base-ftp-am
Name: base-ftp-am
Version: 1.0.2
Release: 3BQ7%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
Source: base-ftp-am.tar.gz
BuildRoot: /tmp/base-ftp-am

%prep
%setup -n base-ftp-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-ftp-am.  

%changelog
* Wed Mar 10 2010 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.2-3BQ7
- remove .svn directory from rpm package.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ6
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ5
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ4
- remove Serial tag.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ3
- clean up spec file.

* Mon Aug 23 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ2
- restart xinetd instead of inetd.

* Sun Jan 11 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ1
- build for Blue Quartz

* Thu Jun 14 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file, add expect style tests

