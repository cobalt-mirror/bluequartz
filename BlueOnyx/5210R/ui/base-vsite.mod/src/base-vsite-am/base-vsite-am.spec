Summary: Active Monitor support for base-vsite-am
Name: base-vsite-am
Version: 1.0.0
Release: 0BX02%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-vsite-am.tar.gz
BuildRoot: /tmp/base-vsite-am

%prep
%setup -n base-vsite-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*
/usr/sausalito/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-vsite-am.  

%changelog

* Tue Sep 05 2017 Michael Stauber <mstauber@solarspeed.net> 1.0.0-0BX02
- Small fix. We now use uniq() to weed out doublettes.

* Sat Aug 26 2017 Michael Stauber <mstauber@solarspeed.net> 1.0.0-0BX01
- Initial build
