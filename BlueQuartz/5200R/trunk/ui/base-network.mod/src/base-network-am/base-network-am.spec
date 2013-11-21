Summary: Binaries and scripts used by Active Monitor for base-network
Name: base-network-am
Version: 1.0.1
Release: 3BQ6%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
Source: base-network-am.tar.gz
BuildRoot: /tmp/%{name}

%prep
%setup -n %{name}

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains a number of binaries and scripts used by the Active
Monitor subsystem to monitor services provided by the base-network module.  

%changelog
* Wed Mar 10 2010 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0.1-3BQ6
- remove .svn directory from rpm package.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ5
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ4
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ3
- remove Serial tag.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ2
- clean up spec file.

* Wed Jan 14 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0.1-3BQ1
- build for Blue Quartz

* Wed Aug 30 2000 Tim Hockin <thockin@cobalt.com>
- initial spec file

