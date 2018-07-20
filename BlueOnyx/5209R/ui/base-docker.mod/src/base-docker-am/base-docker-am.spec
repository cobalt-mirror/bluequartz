Summary: Active Monitor support for base-docker-am
Name: base-docker-am
Version: 1.0.0
Release: 0BX01%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-docker-am.tar.gz
BuildRoot: /tmp/base-docker-am

%prep
%setup -n base-docker-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-docker-am.  

%changelog

* Tue Jul 17 2018 Michael Stauber <mstauber@solarspeed.net> 1.0.0-0BX01
- Initial build.


