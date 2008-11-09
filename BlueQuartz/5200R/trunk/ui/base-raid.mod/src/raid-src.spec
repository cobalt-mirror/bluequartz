Summary: Binaries and scripts used by the RAID module
Name: raid-bins
Version: 1.0.3
Release: 12
Copyright: Sun Microsystems, Inc.  2000-2002
Group: Utils
Source: raid-bins.tar.gz
BuildRoot: /tmp/raid-bins
BuildArchitectures: noarch

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
* Fri Apr 05 2002 Patrick Baltz <patrick.baltz@sun.com>
- make umount_and_raid function of make_raid.sh more robust
  try to umount 10 times before giving up

* Wed Aug 09 2001 Patrick Bose <patrick.bose@sun.com>
- 1.0.3 make_raid.sh sets up correct ui status dir/file permissions

* Thu Feb 15 2001 Patrick Bose <pbose@cobalt.com>
- adding make_raid.sh

* Mon Jun 12 2000 Patrick Bose <pbose@cobalt.com>
- initial spec file

