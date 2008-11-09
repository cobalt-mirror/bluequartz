Summary: Binaries and scripts used by the RAID module
Name: raid-bins
Version: 1.0.1
Release: 1
Copyright: Cobalt Networks 2000
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
/usr/sausalito/perl/Cobalt/RAID.pm

%description
Currently, this rpm contains the scripts used to
determine the status of RAID.

%changelog
* Mon Jun 12 2000 Patrick Bose <pbose@cobalt.com>
- initial spec file

