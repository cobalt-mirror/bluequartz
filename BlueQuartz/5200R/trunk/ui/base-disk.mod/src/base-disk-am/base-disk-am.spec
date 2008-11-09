Summary: Binaries and scripts used by Active Monitor for base-disk
Name: base-disk-am
Version: 1.0.1
Release: 15BQ11%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
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
* Thu Apr 13 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ11
- modify am_disk.pl to fix the issue when gid is NULL by Brian.

* Thu Mar 30 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ10
- The am_disk.pl supports LVM partition.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ9
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ8
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ7
- remove Serial tag.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ6
- add serial number.

* Thu Aug 11 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ5
- clean up spec file.

* Tue May 17 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ4
- modified am_disk.pl. 

* Tue Apr 26 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ3
- The am_disk.pl supports LVM partition.

* Sat Dec 25 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ2
- modified get_quotas.pl to exclude 'games' user.

* Wed Mar 10 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-15BQ1
- build for Blue Quartz
- fix disk active monitor alert

* Tue Jun 20 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

