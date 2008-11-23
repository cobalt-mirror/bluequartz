Summary: Cobalt UI Library
Name: sausalito-palette
Version: 0.5.1
Release: 0BQ16%{?dist}
Vendor: Project BlueQuartz
License: Sun modified BSD
Group: System Environment/BlueQuartz
Source: sausalito-palette.tar.gz
Prefix: /usr/sausalito
BuildRoot: /var/tmp/sausalito-palette-root
BuildArchitectures: noarch

%description
sausalito-palette has all of the Cobalt UI functions.

%prep
%setup -n sausalito-palette

%build
make CCETOPDIR=/usr/sausalito

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/sausalito/ui
make install PREFIX=$RPM_BUILD_ROOT  CCETOPDIR=/usr/sausalito

%post
#Integration code so Monterey can use uifc
if [ -d /usr/admserv/html ]; then
    if [ ! -e /usr/admserv/html/libImage ]; then 
	ln -n -s /usr/sausalito/ui/web/libImage /usr/admserv/html/libImage;
    fi
    if [ ! -e /usr/admserv/html/libJs ]; then 
	ln -n -s /usr/sausalito/ui/web/libJs /usr/admserv/html/libJs;
    fi
    if [ ! -e /usr/admserv/html/nav ]; then 
	ln -n -s /usr/sausalito/ui/web/nav /usr/admserv/html/nav;
    fi
    if [ ! -e /usr/admserv/html/base ]; then 
	ln -n -s /usr/sausalito/ui/web/base /usr/admserv/html/base;
    fi
    if [ ! -e /usr/admserv/html/uifc ]; then 
	ln -n -s /usr/sausalito/ui/web/uifc /usr/admserv/html/uifc;
    fi
fi

%files
%defattr(-,root,root)
/usr/sausalito/ui/menu/palette
/usr/sausalito/ui/conf
/usr/sausalito/ui/libPhp
/usr/sausalito/ui/web
/usr/sausalito/sbin/writeFile.pl
/usr/share/locale/*/LC_MESSAGES/*
/usr/share/locale/*/*.prop
/etc/ccewrap.d/*

%changelog
* Sun Nov 23 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ16
- Brian found the faulty code that cause missing pagedBlock elements:
- Updated libPhp/uifc/PagedBlock.php to change reference to a straight pointer.

* Wed Nov 19 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ15
- Changed default skin to BlueOnyx

* Wed Nov 19 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ14
- Fixed PHP5 related issue in web/status.php

* Wed Sep 10 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ13
- Ajax and JS based password strength checker implemented directly through uifc calls.
- Newly added files for that:
- web/libJs/ajax_lib.js
- web/uifc/check_password.php
- Modified the following pages for that:
- libPhp/uifc/FormFieldBuilder.php
- libPhp/uifc/Password.php
- libPhp/uifc/Page.php

* Tue Sep 09 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ12
- Tab positions in navigation menu (tab.js) fixed for Chrome and Safari. 
- Contributed by Jeremy Knope from rainstormconsulting.com

* Tue Sep 09 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ11
- fixes #10
- fix for Safari 3.0 missing menu for wizard by Anders, BlackSun Inc.

* Sun Jun 08 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ10
- fixes #10
- fix for Safari 3.0 missing menus by Anders, BlackSun Inc.

* Mon May 26 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ9
- Fix in de_DE/palette.po

* Mon Jan 28 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ8
- Fix in de_DE/palette.po

* Sun Jan 27 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ7
- German locales added.

* Tue Jan 22 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ6
- Danish locales added. Thanks to Jes Klittum!

* Mon Jun 25 2007 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ5
- Fixed duplicate include issue.

* Sat May 05 2007 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ4
- Fixed duplicate include issue.
- Fixed PagedBlock.php, fucntion toHtml. Don't call function of non object.

* Fri Aug 04 2006 Brian Smith <brian@nuonce.net> 0.5.1-0BQ3
- Fixed ServerScriptHelper.php, function getFile.  Wouldn't load past 4096 bytes.

* Thu Jul 20 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ2
- add InetAddress class.

* Thu Mar 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ1
- add netmask class.
- merge Merlot style.

* Sun Dec 18 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ10
- modified JavaScript to fix the browser issue with Safari by Anders, BlackSun, Inc.

* Tue Nov 29 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ9
- rebuild with devel-tools 0.5.1-0BQ7.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ8
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ7
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ6
- use PACKAGE_DIR instead of /usr/src/redhat.

* Tue Oct 18 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ5
- rebuild with devel-tools 0.5.1

* Mon Aug 15 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ4
- clean up spec file.

* Wed Aug 10 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ3
- change user length to 31 instead of 12.

* Fri Mar 05 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ2
- fix Japanese charset in palette.prop

* Tue Jan 08 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ1
- build for Blue Quartz

* Wed Nov 01 2000 Philip Martin <pmartin@cobalt.com>
- include the .prop files in the rpm

* Tue May 02 2000 Adrian Sun <asun@cobalt.com>
- make sure the correct permissions get used

* Wed Apr 26 2000 Adrian Sun <asun@cobalt.com>
- renamed

* Thu Mar 09 2000 Adrian Sun <asun@cobalt.com>
- initial palette spec file.
