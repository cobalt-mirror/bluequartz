Summary: Binaries and scripts used by Active Monitor for base-disk
Name: base-disk-am
Version: 1.1.0
Release: 15BX19%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-disk-am.tar.gz
BuildRoot: /tmp/%{name}
Requires: perl-Unix-ConfigFile >= 0.06-SOL1

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
* Sun Jun 06 2010 Michael Stauber <mstauber@solarspeed.net> 1.1.0-15BX19
- On CentOS6 user 'nfsnobody' has UID > 500, so we need to ignore him as well.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 1.1.0-15BQ18
- Rebuilt for BlueOnyx.

* Mon Dec 01 2008 Michael Stauber <mstauber@solarspeed.net> 1.1.0-15BQ17
- Another small fix in get_quota.pl: SITExx-logs users are no longer reported.

* Mon Dec 01 2008 Michael Stauber <mstauber@solarspeed.net> 1.1.0-15BQ16
- Small fix in get_quota.pl. Replaced 'lt' with '<'. One day I'll learn to avoid this kind of mistake.

* Thu Nov 27 2008 Michael Stauber <mstauber@solarspeed.net> 1.1.0-15BQ15
- Since all users are no longer in the 'users' group, quota info couldn't be obtained for sites AND users.
- Updated get_quota.pl to now use UnixConfigFile Perl Module to determine group on demand.
- Streamlined user and group parsing routines in get_quota using Unix::PasswdFile.
- Added requirement for perl-Unix-ConfigFile >= 0.06-SOL1 to specfile.
- Major version bump to 1.1.0 to make clear that this is a radical modify, although 100% compatible to the outside.

* Tue Mar 04 2008 Michael Stauber <mstauber@solarspeed.net> 1.0.1-15BQ14
- Fixed am_disk.pl again. Set safe defaul for $dev if its undefined.

* Sat Mar 01 2008 Michael Stauber <mstauber@solarspeed.net> 1.0.1-15BQ13
- Updated am_disk.pl to address cases where $dev is undefined.

* Sun Feb 03 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.1-15BQ12
- add sign to the package.

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

