Summary: Binaries and scripts used by Active Monitor for base-network
Name: base-network-am
Version: 1.0.1
Release: 4
Copyright: Cobalt Networks 2000
Group: Utils
Source: base-network-am.tar.gz
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
Monitor subsystem to monitor services provided by the base-network module.  

%changelog
* Wed Aug 30 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

