Summary: Binaries and scripts used by Active Monitor for base-disk
Name: base-disk-am
Version: 1.0.1
Release: 15
Copyright: Cobalt Networks 2000
Group: Utils
Source: base-disk-am.tar.gz
BuildRoot: /tmp/%{name}

%prep
%setup -n %{name}

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*
/usr/sausalito/sbin/*

%description
This package contains a number of binaries and scripts used by the Active
Monitor subsystem to monitor services provided by the base-disk module.  

%changelog
* Tue Jun 20 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

