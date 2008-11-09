Summary: Active Monitor support for base-telnet-am
Name: base-telnet-am
Version: 1.0.2
Release: 4
Copyright: Cobalt Networks 2000
Group: Utils
Source: base-telnet-am.tar.gz
BuildRoot: /tmp/base-telnet-am

%prep
%setup -n base-telnet-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-telnet-am.  

%changelog
* Thu Jun 15 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file, add expect style tests

