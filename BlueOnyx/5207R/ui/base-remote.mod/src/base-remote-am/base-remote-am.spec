Summary: Active Monitor support for base-remote-am
Name: base-remote-am
Version: 1.3.0
Release: 1BX01%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-remote-am.tar.gz
BuildRoot: /tmp/base-remote-am

%prep
%setup -n base-remote-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-remote-am.  

%changelog

* Wed Nov 24 2016 Michael Stauber <mstauber@solarspeed.net> 1.3.0-1BX01
- Initial build.


