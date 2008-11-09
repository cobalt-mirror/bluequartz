Summary: Active Monitor support for base-ftp-am
Name: base-ftp-am
Version: 1.0.2
Release: 3BQ2%{?dist}
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
* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ2
- add dist tag for release.

* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ1
- build for BlueQuartz 5100WG.

* Thu Jun 14 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file, add expect style tests

