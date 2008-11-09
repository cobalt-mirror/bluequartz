Summary: Perl modules that contain useful utility functions for handlers.
Name: perl-handler-utils
Version: 1.3.0
Release: 11
Copyright: Sun Microsystems, Inc.  2000-2002
Group: Silly
Source: perl-handler-utils.tar.gz
BuildRoot: /tmp/perl-sauce

%prep
%setup -n perl-handler-utils

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/perl/Sauce/Config.pm
/usr/sausalito/perl/Sauce/Util.pm
/usr/sausalito/perl/Sauce/Validators.pm
/usr/sausalito/perl/Sauce/Service.pm
/usr/sausalito/perl/Sauce/Service
/usr/sausalito/perl/Sauce/Util
/usr/sausalito/sbin/txnend
/usr/sausalito/sbin/txnrollback

%description
This package contains a number of perl modules that contain useful
utility functions for writing cced event handler scripts.

%changelog
* Thu Apr  4 2002 Patrick Baltz <patrick.baltz@sun.com> 1.3.0-11
- pr 14438.  Add Sauce::Service::Daemon and Client.  This queues init
  script runs that get registered with it to prevent race conditions.

* Thu Mar 21 2002 Patrick Baltz <patrick.baltz@sun.com> 1.3.0-10
- pr 14310.  The domain name validator function was incorrectly identifying
  a one part domain (no '.'s) as blank.

* Mon Nov 19 2001 Patrick Baltz <patrick.baltz@sun.com>
- bump release so changes to SecurityLevels.pm get into build

* Tue Jun 26 2001 Patrick Baltz <patrick.baltz@sun.com>
- add new Sauce/Util/ sub-directory to rpm

* Sun Apr 29 2000 Jonathan Mayer <jmayer@cobalt.com>
- initial spec file

