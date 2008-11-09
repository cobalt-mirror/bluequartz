Summary: Active Monitor support for base-dns-am
Name: base-dns-am
Version: 1.0
Release: 2
Copyright: Cobalt Sun Microsystems 2001, All rights reserved.
Group: Utils
Source: base-dns-am.tar.gz
BuildRoot: /tmp/base-dns-am

%prep
%setup -n base-dns-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-dns-am.  

%changelog
* Mon Jul 23 2001 Will DeHaan <null@sun.com>
- Initial spec file

