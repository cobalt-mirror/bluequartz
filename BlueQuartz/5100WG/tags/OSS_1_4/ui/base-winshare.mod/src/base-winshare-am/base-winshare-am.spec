Summary: Active Monitor support for base-winshare-am
Name: base-winshare-am
Version: 1.0.1
Release: 1
Copyright: Cobalt Networks 2000
Group: Utils
Source: base-winshare-am.tar.gz
BuildRoot: /tmp/base-winshare-am

%prep
%setup -n base-winshare-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-winshare-am.  

%changelog
* Wed Jun 28 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

