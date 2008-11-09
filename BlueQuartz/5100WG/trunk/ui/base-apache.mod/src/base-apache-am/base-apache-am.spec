Summary: Active Monitor support for base-apache-am
Name: base-apache-am
Version: 1.0.2
Release: 3BQ2%{?dist}
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
* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ2
- add dist tag for release.

* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.2-3BQ1
- build for BlueQuartz 5100WG.

* Fri Sep 19 2003 Hisao SHIBUYA <shibuya@alpha.or.jp>
- (1.0.2-3OQ1)
- build for Open Qube.

* Thu Jun 14 2001 James Cheng <james.y.cheng@sun.com>
- add expect style tests
* Wed Jun 28 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

