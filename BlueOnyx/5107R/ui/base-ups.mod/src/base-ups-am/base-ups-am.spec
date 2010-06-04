Summary: Binaries and scripts used by Active Monitor for base-ups
Name: base-ups-am
Version: 1.0.1
Release: 1BX01
License: Sun Microsystems 2001
Group: Utils
Source: base-ups-am.tar.gz
BuildRoot: /tmp/%{name}

%prep
%setup -n %{name}

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains a number of binaries and scripts used by the Active
Monitor subsystem to monitor services provided by the base-ups module.

%changelog
* Fri Jun 04 2010 Michael Stauber <mstauber@blueonyx.it> 1.0.1-1BX01
- Version number bump

* Tue Dec  8 2009 Rickard Osser <rickard.osser@bluapp.com>
- Updated to be compatible with nut-2.4.1

* Mon Apr 30 2001 Joshua Uziel <uzi@sun.com>
- initial spec file

