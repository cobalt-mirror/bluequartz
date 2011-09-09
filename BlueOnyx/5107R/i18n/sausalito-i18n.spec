%define extension_dir %(php-config --extension-dir)

Summary: Cobalt i18n library
Name: sausalito-i18n
Version: 0.70.2
Release: 86BX21%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: sausalito-i18n.tar.gz
Prefix: /usr/sausalito
BuildRoot: /var/tmp/sausalito-i18n-root
Requires: glib >= 1.2.7
Requires: base-admserv-glue >= 1.0.1
%if "%sles" == "9"
BuildRequires: php4-devel
%endif
%if "%tlas" == "2"
BuildRequires: php4
%endif
%if 0%{?fedora}%{?centos}
BuildRequires: php-devel
%endif

%description
sausalito-i18n is a wrapper library for i18n functions.

%package devel
Summary: The Sausalito i18n development libraries.
Group: System Environment/BlueOnyx

%description devel
sausalito-i18n-devel includes the include files and static libraries for the 
Sausalito i18n functions.

%prep
%setup -n sausalito-i18n

%build
make

%install
rm -rf $RPM_BUILD_ROOT
make install PREFIX=$RPM_BUILD_ROOT CCETOPDIR=/usr/sausalito
rm -f $RPM_BUILD_ROOT/usr/sausalito/ui/web/test_i18n.php

%files
%defattr(-,root,root)
/usr/sausalito/lib/*.so*
/usr/sausalito/bin/i18n_get
/usr/sausalito/bin/i18n_translate
/usr/sausalito/bin/i18n_locales
/usr/sausalito/ui/libPhp/*
%{extension_dir}/*
/usr/sausalito/perl/I18n.pm
/usr/sausalito/perl/SendEmail.pm
/usr/sausalito/perl/I18nMail.pm
/usr/sausalito/perl/JConv.pl
/usr/sausalito/bin/i18nmail.pl
/usr/share/dict/cracklib-words 
/usr/share/dict/pw_dict.hwm 
/usr/share/dict/pw_dict.pwd 
/usr/share/dict/pw_dict.pwi 

%files devel
%defattr(-,root,root)
/usr/sausalito/lib/*.a
/usr/sausalito/include/cce/*.h

%post
# only run on install, not upgrade
if [ "$1" = 1 ]; then
	LIB=/usr/sausalito/lib
	cp /etc/ld.so.conf /etc/ld.so.conf.bak
	egrep "^$LIB[ 	]*$" /etc/ld.so.conf >/dev/null \
		|| echo $LIB >> /etc/ld.so.conf
fi
/sbin/ldconfig

# make sure these links exist or setlocale doesn't work right
# temporary fix for now.  try both just to make sure this happens
if [ -d "/usr/lib/locale/en_US" ]; then
	ln -sf /usr/lib/locale/en_US /usr/lib/locale/en
fi
if [ -d "/usr/share/locale/en_US" ]; then
	ln -sf /usr/share/locale/en_US /usr/share/locale/en
fi
if [ -d "/usr/lib/locale/ja_JP.eucjp" ]; then
	ln -sf /usr/lib/locale/ja_JP.eucjp /usr/lib/locale/ja
fi
if [ -d "/usr/share/locale/ja_JP.eucjp" ]; then
	ln -sf /usr/share/locale/ja_JP.eucjp /usr/share/locale/ja
fi

%changelog
* Fri Sep 09 2011 Michael Stauber <mstauber@solarspeed.net> 0.70.2-86BX23
- On 5107R the GUI defaulted to something non English (mostly German) if the
  browser locale wasn't one of our supported locales. I tried to fix this
  through work arounds both in perl/I18n.pm and php/I18n.php
- Modified perl/I18n.pm to make sure 'en_US' comes first in the list of
  supported locales.
- Modified php/I18n.php to make sure that if no supported locale is detected,
  then 'en_US' will be used instead.

* Sat Jun 03 2010 Michael Stauber <mstauber@solarspeed.net> 0.70.2-86BX22
- Version number bump due to PHP upgrade to PHP-5.3.2 in RHEL 6 Beta 2

* Wed Jun 02 2010 Michael Stauber <mstauber@solarspeed.net> 0.70.2-86BX20
- Change of minor version number to BX

* Mon Jun 22 2009 Rickard osser <rickard.osser@bluapp.com> 0.70.2-86BQ19
- Fixed bug which added characters to mime-encoded headers in I18nMail.pm.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 0.70.2-86BQ18
- Rebuilt for BlueOnyx.

* Sun Jun 01 2008 Michael Stauber <mstauber@solarspeed.net> 0.70.2-86BQ17
- Merged 5200R and 5100R code for 5106R

* Tue May 13 2008 Michael Stauber <mstauber@solarspeed.net> 0.70.2-86BQ16
- Added source directory /crack for compilation of crack.so support (cracklib PHP extension)

* Fri Feb 08 2008 Michael Stauber <mstauber@solarspeed.net> 0.70.2-86BQ15
- Modified I18nMail.pm to add Danish and German locale support.

* Sun Jan 27 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 0.70.2-86BQ14
- add sign to the package.

* Sun May 06 2007 Hisao SHIBUYA <shibuya@bluequartz.org> 0.70.2-86BQ15
- modify I18n.php to fix array error.

* Mon Apr 30 2007 Hisao SHIBUYA <shibuya@bluequartz.org> 0.70.2-86BQ14
- remove -static for building i18n tools.

* Fri Feb 17 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ13
- modify EncodingConv.php to fix failed to convert from unknown encoding.

* Mon Jan 16 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ12
- remove i18n.ini for php.

* Tue Nov 29 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ11
- rebuild with devel-tools 0.5.1-0BQ7.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ10
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ9
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ8
- use php-config to get extension directory.

* Thu Oct 20 2005 Hisao SHIBUYA <shibuya@turbolinux.co.jp> 0.70.2-86BQ7
- modified spec.in file for TLAS

* Tue Oct 18 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ6
- rebuild with devel-tools 0.5.1

* Mon Aug 15 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ5
- modified Group tag.

* Mon Aug 15 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ4
- clean up spec file.

* Sat Jun 11 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ3
- support x86_64 environment

* Tue Jan 08 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.70.2-86BQ1
- build for Blue Quartz

* Thu Mar 14 2002 Patrick Baltz <patrick.baltz@sun.com>
- pr 14167.  perl I18n::interpolate doesn't work without the appropriate
  symlinks, because of setlocale

* Tue Jun 27 2000 Tim Hockin <thockin@cobalt.com>
- Rev bump, try to figure out why ldconfig is not run on new builds

* Fri Jun 16 2000 Tim Hockin <thockin@cobalt.com>
- make sure /usr/sausalito/lib is in ld.so.conf

* Mon May 29 2000 Patrick Bose <pbose@cobalt.com>
- 0.11-1 adding perl client library

* Wed Apr 26 2000 Adrian Sun <asun@cobalt.com>
- renamed 

* Tue Mar 15 2000 Adrian Sun <asun@cobalt.com>
- re-worked source tree

* Tue Feb 29 2000 Adrian Sun <asun@cobalt.com>
- added I18n.php install

* Fri Feb 25 2000 Adrian Sun <asun@cobalt.com>
- initial version
