Summary: Binaries and scripts used by the Active Monitor subsytem
Name: am-bins
Version: 1.1.1
Release: 21
Copyright: Sun Microsystems, Inc. 2001
Group: Utils
Source: am-bins.tar.gz
BuildRoot: /tmp/am-bins

%prep
rm -rf $RPM_BUILD_ROOT

%setup -n am-bins

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*
%dir /usr/sausalito/perl/AM
/usr/sausalito/perl/AM/Util.pm

%description
This package contains a number of binaries and scripts used by the Active
Monitor subsystem.  These include programs to check the state of the CPU
and memory usage.  Also, this includes the AM::Util perl module.

%changelog
* Wed Jun 28 2000 Tim Hockin <thockin@cobalt.com>
- Add AM::Util.pm

* Fri May 26 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

