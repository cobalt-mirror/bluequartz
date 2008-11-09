Summary: Perl modules that contain vital telnet access functionality
Name: telnet-scripts
Version: 1.1.2
Release: 6BQ5%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
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

%changelog
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

