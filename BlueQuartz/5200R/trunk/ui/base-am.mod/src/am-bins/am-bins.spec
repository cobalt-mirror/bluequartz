Summary: Binaries and scripts used by the Active Monitor subsytem
Name: am-bins
Version: 1.1.1
Vendor: %{vendor}
Release: 21BQ9%{?dist}
License: Sun modified BSD
Group: System Environment/BlueQuartz
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
* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-21BQ9
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-21BQ8
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-21BQ7
- remove Serial tag.

* Mon Oct 10 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-21BQ6
- modified am_mem.pl to fix the issue that support kernel 2.4 and 2.6.

* Fri Aug 11 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-21BQ5
- add serial number.

* Thu Aug 11 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-21BQ4
- clean up spec file.

* Wed Apr 13 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-21BQ3
- modified am_mem.pl to support kernel 2.6 by Rickard Osser.
- remove Cobalt hardware dependency script such as am_temp.pl and am_fans.pl.

* Mon Mar 15 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-21BQ2
- remove am_ecc.pl

* Tue Jan 08 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-21BQ1
- build for Blue Quartz

* Wed Jun 28 2000 Tim Hockin <thockin@cobalt.com>
- Add AM::Util.pm

* Fri May 26 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

