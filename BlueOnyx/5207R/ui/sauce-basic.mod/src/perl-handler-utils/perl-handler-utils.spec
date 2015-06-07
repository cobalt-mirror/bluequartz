Summary: Perl modules that contain useful utility functions for handlers.
Name: perl-handler-utils
Version: 1.4.0
Release: 0BX05%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: perl-handler-utils.tar.gz
BuildRoot: /tmp/perl-sauce
Provides: perl(Sauce::Util::SecurityLevels)

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

* Sat Jun 06 2015 Michael Stauber <mstauber@solarspeed.net> 1.4.0-0BX05
- Small fixes in Sauce/Service/Daemon.pm for the binary locations.

* Sat Jun 06 2015 Michael Stauber <mstauber@solarspeed.net> 1.4.0-0BX04
- Reloading 'httpd' is tricky for a multitude of reasons. One being that
  systemctl doesn't reload when the service is stopped. Or we have the
  case where Apache child processes have detached from the master.
  Likewise we need to avoid restarting/reloading Apache in quick 
  succession, which sadly is done by certain events that affect both
  server wide settings and Vsite wide settings. Each causes a reload or
  a restart. Sausalito has a Client/Daemon implementation in Sauce::Service
  for this purpose. If Apache is triggered to be restarted or reloaded,
  then this spawns a Client/Daemon that waits for a moment to see if there
  is only one request, or multiple. At the end of all requests it performs
  the desired action only once. This needed to be adapted to handle both
  InitV and Systemd, which I just did. It also needed to be adapted to
  really check if Apache is now running, or if a reload needed to be
  upgraded to a restart if Apache is dead. It also kills off detached
  child processes and does a restart (and health check) afterwards.
  If we can, we will reload when a reload was called. If that doesn't
  work, we will kill off Apache and restart it and then check if it is
  really up. 
- Modified Sauce/Service.pm to hand off Apache to Daemon/Client.
- Modified Sauce/Service/Client.pm for better logging.
- Extended Sauce/Service/Daemon.pm to do better logging, added health
  checks, checks if services run properly and loads of special
  provisions to deal with Apache to begin with. Also added support for
  both InitV and Systemd.

* Sun Jan 25 2015 Michael Stauber <mstauber@solarspeed.net> 1.4.0-0BX03
- More Systemd related fixes. Use systemctl if present and if we're
  stopping or starting a service we use --no-block without exception.

* Thu Dec 04 2014 Michael Stauber <mstauber@solarspeed.net> 1.4.0-0BX02
- More Systemd related fixes.

* Wed Dec 03 2014 Michael Stauber <mstauber@solarspeed.net> 1.4.0-0BX01
- Modified Sauce/Service.pm to deal with Systemd if present.

* Wed Aug 31 2011 Michael Stauber <mstauber@solarspeed.net> 1.3.1-0BX02
- Modified Sauce/Service.pm with a special case for 'crond' to prevent forking
  multiple 'crond' instances.

* Fri Jul 01 2011 Michael Stauber <mstauber@solarspeed.net> 1.3.1-0BX01
- Modified Sauce/Util.pm with a debugging switch that helps us to find 
  troublesome Sauce:editfile calls.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 1.3.0-11BQ13
- Rebuilt for BlueOnyx.

* Mon Dec 01 2008 Michael Stauber <mstauber@solarspeed.net> 1.3.0-11BQ12
- Modified hash_edit_function in Sauce/Util.pm to ignore php.ini style section headers.

* Sat May 05 2007 Hisao SHIBUYA <shibuya@bluequartz.org> 1.3.0-11BQ11
- change option -l for tar 1.15 again.

* Thu May 03 2007 Hisao SHIBUYA <shibuya@bluequartz.org> 1.3.0-11BQ10
- change option -l for tar 1.15.

* Fri Nov 25 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.3.0-11BQ9
- fixed the issue that permission of / partision.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.3.0-11BQ8
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.3.0-11BQ7
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.3.0-11BQ6
- remove Serial tag.

* Mon Aug 15 2005 Hisao SHIBUYA <shibuya@alpha.or.jP. 1.3.0-11BQ5
- celan up spec file.

* Thu Aug 11 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.3.0-11BQ4
- modified Service.pm to use chkconfig $service off instead of chkconfig --del.

* Thu Dec 16 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.3.0-11BQ3
- modified Validators.pm to fix validate domainname.

* Tue Nov 23 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.3.0-11BQ2
- modified Service.pm to fix httpd defunct problem.

* Tue Jan 08 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.3.0-11BQ1
- build for Blue Quartz

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

