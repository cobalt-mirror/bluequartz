Summary: Active Monitor support for base-ftp-am
Name: base-ftp-am
Version: 1.0.2
Release: 3
Copyright: Cobalt Networks 2000
Group: Utils
Source: base-ftp-am.tar.gz
BuildRoot: /tmp/base-ftp-am

%prep
%setup -n base-ftp-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-ftp-am.  

%changelog
* Thu Jun 14 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file, add expect style tests

