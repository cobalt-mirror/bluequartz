Summary: Active Monitor support for base-email-am
Name: base-email-am
Version: 1.0.2
Release: 3BQ3%{?dist}
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
* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ3
- add dist tag for release.

* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ2
- build for BlueQuartz 5100WG.

* Thu Jun 15 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file, add expect style tests

