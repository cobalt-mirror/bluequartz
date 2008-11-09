Summary: Binaries and scripts used by the Active Monitor subsytem
Name: am-bins
Version: 1.1.1
Release: 1BQ1%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
Source: am-bins.tar.gz
BuildRoot: /tmp/am-bins

%prep
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
* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-1BQ1
- build for BlueQuartz 5100WG.

* Thu Sep 11 2003 Hisao SHIBUYA <shibuya@alpha.or.jp>
- (1.1.1-1OQ1)
- build for Red Hat Linux 9.

* Wed Jun 28 2000 Tim Hockin <thockin@cobalt.com>
- Add AM::Util.pm

* Fri May 26 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

