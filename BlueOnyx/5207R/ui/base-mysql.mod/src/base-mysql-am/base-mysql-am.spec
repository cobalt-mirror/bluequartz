Summary: Active Monitor support for base-telnet-am
Name: base-mysql-am
Version: 2.0.0
Release: 2%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-mysql-am.tar.gz
BuildRoot: /tmp/base-mysql-am
Obsoletes: nuonce-mysql-am

%prep
%setup -n base-mysql-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*
#/usr/bin/check-mysql.sh

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-mysql-am.  

%changelog

* Thu Dec 04 2008 Michael Stauber <mstauber@solarspeed.net> 2.0.0-2
- Systemd related fixes.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 2.0.0-1
- Rebuilt the NuOnce nuonce-mysql-am for BlueOnyx
