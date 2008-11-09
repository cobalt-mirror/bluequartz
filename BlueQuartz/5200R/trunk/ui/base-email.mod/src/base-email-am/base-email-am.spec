Summary: Active Monitor support for base-email-am
Name: base-email-am
Version: 1.1.0
Release: 0BQ1%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
Source: base-email-am.tar.gz
BuildRoot: /tmp/base-email-am

%prep
%setup -n base-email-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-email-am.  

%changelog
* Sat Jun 10 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.0-0BQ1
- change scripts for dovecot.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ6
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ5
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpah.or.jp> 1.0.2-4BQ4
- remove Serial tag.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpah.or.jp> 1.0.2-4BQ3
- add Serial tag.

* Thu Aug 11 2005 Hisao SHIBUYA <shibuya@alpah.or.jp> 1.0.2-4BQ2
- clean up spec file.

* Tue Jan 08 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-4BQ1
- build for Blue Quartz

* Thu Jun 15 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file, add expect style tests

