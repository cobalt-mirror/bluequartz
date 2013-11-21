Summary: Binaries and scripts used by the RAID module
Name: raid-bins
Version: 1.0.3
Release: 12BQ4%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: Utils
Source: raid-bins.tar.gz
BuildRoot: /tmp/raid-bins

%prep
rm -rf $RPM_BUILD_ROOT

%setup -n src

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/raidState.pl
/usr/sausalito/swatch/bin/raid_amdetails.pl
/usr/sausalito/perl/Cobalt/RAID.pm
/usr/local/sbin/make_raid.sh
/usr/sausalito/swatch/bin/smart-status.pl
/usr/local/sbin/ide-smart
/usr/sausalito/swatch/bin/dma_test.pl

%description
Currently, this rpm contains the scripts used to
determine the status of RAID.

%changelog
* Mon Feb 22 2010 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.3-12BQ4
- remove ide-smart binary.

* Mon Feb 22 2010 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.3-12BQ3
- remove BuildArchitectures tag because of arch dependent binariy is exist.

* Wed Sep 16 2009 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.3-12BQ2
- remove copyright tag and add license tag at spec file.

* Sun Feb 03 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.3-12BQ1
- add dist macro for release and add sign to the package.

* Fri Apr 05 2002 Patrick Baltz <patrick.baltz@sun.com>
- make umount_and_raid function of make_raid.sh more robust
  try to umount 10 times before giving up

* Wed Aug 09 2001 Patrick Bose <patrick.bose@sun.com>
- 1.0.3 make_raid.sh sets up correct ui status dir/file permissions

* Thu Feb 15 2001 Patrick Bose <pbose@cobalt.com>
- adding make_raid.sh

* Mon Jun 12 2000 Patrick Bose <pbose@cobalt.com>
- initial spec file

