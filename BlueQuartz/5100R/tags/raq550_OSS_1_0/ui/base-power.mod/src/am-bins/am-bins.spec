Summary: Binaries and scripts used to monitor Power subsystems
Name: base-power-am
Version: 1.1.1
Release: 7
Copyright: Sun Microsystems, Inc. 2001
Group: Utils
Source: am-bins.tar.gz
BuildRoot: /tmp/am-bins

%prep
%setup -n am-bins

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains a number of binaries and scripts used by Active
Monitor to monitor the power subsystem.  These include programs to
check the state of the power supply and the cmos battery usage.

%changelog
* Thu Oct 18 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file

