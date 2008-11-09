Summary: Config files describing Alpine hardware
Name: base-alpine-hardware-conf
Version: 1.0.0
Release: 14
Copyright: Sun Microsystems, Inc. 2001
Group: Utils
Source: base-alpine-hardware-conf.tar.gz
BuildRoot: /tmp/%{name}

%prep
%setup -n %{name}

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/*

%description
This package contains a number of scripts and config files used by Alpine.

%changelog
* Fri Sep 14 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file

