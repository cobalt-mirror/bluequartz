Summary: Binaries and scripts used by Active Monitor for base-ups
Name: base-ups-am
Version: 1.0.0
Release: 6
Copyright: Sun Microsystems 2001
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
* Mon Apr 30 2001 Joshua Uziel <uzi@sun.com>
- initial spec file

