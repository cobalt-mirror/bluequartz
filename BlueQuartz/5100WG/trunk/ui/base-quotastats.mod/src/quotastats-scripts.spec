Summary: Perl script to gather disk and quota stats
Name: quotastats-scripts
Version: 1.01.1
Release: 2BQ1%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
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
* Sun Jul 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.01.1-2BQ1
- build for BlueQuartz 5100WG.

* Wed Aug 02 2000 Phil Ploquin <pploquin@cobalt.com>
- initial spec file.

