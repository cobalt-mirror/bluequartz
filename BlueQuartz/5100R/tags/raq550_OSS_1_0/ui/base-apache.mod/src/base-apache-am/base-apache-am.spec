Summary: Active Monitor support for base-apache-am
Name: base-apache-am
Version: 1.0.2
Release: 4
Copyright: Cobalt Networks 2000
Group: Utils
Source: base-apache-am.tar.gz
BuildRoot: /tmp/base-apache-am

%prep
%setup -n base-apache-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-apache-am.  

%changelog
* Wed Jan 23 2002 James Cheng <james.y.cheng@sun.com>
- fix to check port 444 for admserv, not port 81
* Thu Jun 14 2001 James Cheng <james.y.cheng@sun.com>
- add expect style tests
* Wed Jun 28 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

