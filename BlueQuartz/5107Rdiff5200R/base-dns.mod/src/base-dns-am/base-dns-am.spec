Summary: Active Monitor support for base-dns-am
Name: base-dns-am
Version: 1.0
Release: 2BQ7%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
Source: base-dns-am.tar.gz
BuildRoot: /tmp/base-dns-am

%prep
%setup -n base-dns-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-dns-am.  

%changelog
* Wed Mar 10 2010 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0-2BQ7
- remove .svn directory from rpm package.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0-2BQ6
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0-2BQ5
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0-2BQ4
- remove Serial tag.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpah.or.jp> 1.0-2BQ3
- add serial number.

* Thu Aug 11 2005 Hisao SHIBUYA <shibuya@alpah.or.jp> 1.0-2BQ2
- clean up spec file.

* Sun Jan 11 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.0-2BQ1
- build for Blue Quartz

* Mon Jul 23 2001 Will DeHaan <null@sun.com>
- Initial spec file

