Summary: Active Monitor support for base-dns-am
Name: base-dns-am
Version: 1.1
Release: 0BX04%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
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

* Thu Jan 28 2016 Michael Stauber <mstauber@solarspeed.net> 1.1-0BX05
- Better implementation of Unit-File fix on demand in am_dns.sh.

* Thu Jan 28 2016 Michael Stauber <mstauber@solarspeed.net> 1.1-0BX04
- Modified am_dns.sh to call constructor fixDNS.pl if needed. Which 
  has provisions to fix up the Systemd Unit-File named-chroot in case
  a YUM update rolls it back to a nonfunctional version.

* Mon Dec 22 2014 Michael Stauber <mstauber@solarspeed.net> 1.1-0BX03
- More Systemd related changes.

* Thu Dec 04 2014 Michael Stauber <mstauber@solarspeed.net> 1.1-0BX02
- Systemd related changes.

* Thu Dec 05 2013 Michael Stauber <mstauber@solarspeed.net> 1.1-0BX01
- Removed .svn directory from rpm package. 

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 1.0-2BQ8
- Rebuilt for BlueOnyx.

* Sun Feb 03 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 1.0-2BQ7
- add sign to the package.

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

