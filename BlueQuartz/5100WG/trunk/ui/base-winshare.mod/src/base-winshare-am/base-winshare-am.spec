Summary: Active Monitor support for base-winshare-am
Name: base-winshare-am
Version: 1.0.1
Release: 1BQ1%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
Source: base-winshare-am.tar.gz
BuildRoot: /tmp/base-winshare-am

%prep
%setup -n base-winshare-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-winshare-am.  

%changelog
* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-1BQ1
- build for BlueQuartz 5100WG.

* Wed Jun 28 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

