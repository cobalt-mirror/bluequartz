Summary: Active Monitor support for base-mailman-am
Name: base-mailman-am
Version: 1.0.0
Release: 1BX02%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-mailman-am.tar.gz
BuildRoot: /tmp/base-mailman-am

%prep
%setup -n base-mailman-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-mailman-am.  

%changelog

* Thu Dec 04 2014 Michael Stauber <mstauber@solarspeed.net> 1.0.0-1BX02
- Systemd related fixes.

* Tue Apr 26 2011 Michael Stauber <mstauber@solarspeed.net> 1.0.0-1BX01
- Active Monitor component to monitor MailMan status.
