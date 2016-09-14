Summary: Active Monitor support for base-ssh-am
Name: base-ssh-am
Version: 1.0.0
Release: 1BX01%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-ssh-am.tar.gz
BuildRoot: /tmp/base-ssh-am

%prep
%setup -n base-ssh-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-ssh-am.  

%changelog

* Tue Sep 13 2016 Michael Stauber <mstauber@solarspeed.net> 1.0.0-1BX01
- Initial build.


