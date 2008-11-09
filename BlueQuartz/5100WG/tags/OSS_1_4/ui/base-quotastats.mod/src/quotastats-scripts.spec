Summary: Perl script to gather disk and quota stats
Name: quotastats-scripts
Version: 1.01.1
Release: 2
Copyright: Cobalt Networks 2000
Group: Silly
Source: quotastats-scripts.tar.gz
BuildRoot: /tmp/quotastats-scripts

%prep
%setup -n src

%build
rm -rf $RPM_BUILD_ROOT 
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/sbin/get_quota_stats.pl

%description
This package contains the perl script that collects the disk and quota
statistics.

%changelog
* Wed Aug 02 2000 Phil Ploquin <pploquin@cobalt.com>
- initial spec file.

