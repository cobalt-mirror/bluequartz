Summary: Active Monitor support for base-dns-am
Name: base-dns-am
Version: 1.0
Release: 2BQ2%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
Source: base-dns-am.tar.gz
BuildRoot: /tmp/base-dns-am

%prep
%setup -n base-dns-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-dns-am.  

%changelog
* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0-2BQ2
- add dist tag for release.

* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0-2BQ1
- build for BlueQuartz 5100WG.

* Fri Oct 03 2003 Hisao SHIBUYA <shibuya@alpha.or.jp>
- (1.0-2OQ1)
- build for Open Qube.

* Mon Jul 23 2001 Will DeHaan <null@sun.com>
- Initial spec file

