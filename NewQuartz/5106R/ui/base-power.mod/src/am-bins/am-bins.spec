Summary: Binaries and scripts used to monitor Power subsystems
Name: base-power-am
Version: 1.1.1
Release: 7BQ4
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

%description
This package contains a number of binaries and scripts used by Active
Monitor to monitor the power subsystem.  These include programs to
check the state of the power supply and the cmos battery usage.

%changelog
* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-7BQ4
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-7BQ3
- remove Serial tag.

* fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-7BQ2
- clean up spec file.

* Wed Jan 21 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.1-7BQ1
- build for Blue Quartz

* Thu Oct 18 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file

