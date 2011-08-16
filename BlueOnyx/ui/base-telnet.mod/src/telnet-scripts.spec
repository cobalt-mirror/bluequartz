Summary: Perl modules that contain vital telnet access functionality
Name: telnet-scripts
Version: 1.1.3
Release: 7BX01%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: telnet-scripts.tar.gz
BuildRoot: /tmp/telnet-scripts

%prep
%setup -n src

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install
rm -rf $RPM_BUILD_ROOT/usr/sausalito/swatch

%files
/usr/sausalito/perl/TelnetAccess.pm
/usr/sausalito/sbin/telnetAccess.pl
/usr/sausalito/sbin/initTelnet.sh

%description
This package contains a number of scripts and perl modules that
contain vital functionality for telnet access.

%post
if [ -e /usr/sausalito/sbin/initTelnet.sh ];then
    /usr/sausalito/sbin/initTelnet.sh > /dev/null 2>&1
fi

%changelog

* Tue Jun 08 2010 Michael Stauber <mstauber@solarspeed.net> 1.1.3-7BX01
- initTelnet.sh used to get run by BTO? Makes no sense, we can do it on post install.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 1.1.2-6BQ7
- Rebuilt for BlueOnyx.

* Sun Feb 03 2008 Hisao SHIBUYA <shibuya@bleuquartz.org> 1.1.2-6BQ6
- add sign to the package.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.2-6BQ5
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.2-6BQ4
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.2-6BQ3
- clean up spec file.
- use PACKAGE_DIR instead of /usr/src/redhat.

* Fri May 28 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.2-6BQ2
- support xinetd

* Mon Jan 12 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.1.2-6BQ1
- build for Blue Quartz

* Tue Aug 20 2002 Sam Napolitano <sam.napolitano@sun.com>
- PR 15674: initTelnet.sh also creates an /etc/shells-deny with /bin/badsh
 
* Tue Mar 5 2002 Patrick Baltz <patrick.baltz@sun.com>
- bug 14058.  Shell access is not permitted for /bin/badsh.

* Sat May 5 2001 Patrick Baltz <patrick.baltz@sun.com>
- fix exploding handler initTelnet.sh to say goodbye

* Mon Oct 02 2000 Patrick Baltz <pbaltz@cobalt.com>
- just adding this comment to see if bto problems are fixed

* Tue Sep 5 2000 Phil Ploquin <pploquin@cobalt.com>
- initial spec file.

