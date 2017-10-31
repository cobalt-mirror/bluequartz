Summary: Active Monitor support for base-netdata-am
Name: base-netdata-am
Version: 1.0.0
Release: 0BX01%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-netdata-am.tar.gz
BuildRoot: /tmp/base-netdata-am

%prep
%setup -n base-netdata-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-netdata-am.  

%changelog

* Tue Dec 20 2016 Michael Stauber <mstauber@solarspeed.net> 1.0.0-0BX01
- Initial build.


