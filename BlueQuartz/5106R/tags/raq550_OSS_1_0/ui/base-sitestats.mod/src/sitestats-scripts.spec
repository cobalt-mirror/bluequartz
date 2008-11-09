Summary: Server and site statistics for web, ftp, email, and network traffic
Name: base-sitestats-scripts
Version: 1.0
Release: 25
Copyright: Sun Microsystems, Inc. 1997-2002
Group: Base
Source: sitestats-scripts.tar.gz
BuildRoot: /tmp/sitestats-scripts
BuildArchitectures: noarch

%description
This package contains the scripts for processing logfiles
and monitoring network traffic and the php user interface for 
generating and viewing reports.

%changelog
* Wed Mar 6 2002 Joshua Uziel <uzi@sun.com> 1.0-25
- fix pr 14072, we were using ' ' instead of '\s+' for whitespace.

* Wed Feb 13 2002 Patrick Baltz <patrick.baltz@sun.com> 1.0-24
- fix pr 13600.  deal with logrotate not being run daily.  restore XTR behavior
  of clumping days if logrotate doesn't run for some reason rather than losing
  stats information.

* Tue Feb 12 2002 Patrick Baltz <patrick.baltz@sun.com> 1.0-23
- fix pr 13872.  cache local user lookups, so we don't have to hit
  CCE as often.  This speeds things up tremendously when most of the
  email is from/to a local user name.

* Fri Jan 18 2002 Patrick Baltz <patrick.baltz@sun.com> 1.0-22
- hopefully address pr 13601.  Need to look back one day so we
  get a 12:00 am - 12:00 pm period for each day when generating
  analog stats cache files.

* Thu Jan 17 2002 Patrick Baltz <patrick.baltz@sun.com> 1.0-21
- fix prs 13600 and 13510.  Replace default apache logrotate file
  with one that rotates the access log daily.

* Fri Jan 11 2002 Patrick Baltz <patrick.baltz@sun.com> 1.0-20
- fix pr 12619.  Don't include index.html files as a directory by
  changing DIRSUFFIX for analog config to a file name that will most likely
  never be used.

* Thu Jan 10 2002 Patrick Baltz <patrick.baltz@sun.com> 1.0-19
- fix pr 13329.  show all sub domains for domains displayed in the domain
  report.

* Mon Jan 7 2002 Patrick Baltz <patrick.baltz@sun.com> 1.0-18
- fix pr 13432.  make sure $Htgroup_dir exists in split_logs before
  doing anything else

* Fri Jan 4 2002 Patrick Baltz <patrick.baltz@sun.com> 1.0-17
- fix pr 13399.  make sure only the correct day gets put in the stat
  cache for a particular day.

* Wed Jan 2 2002 Patrick Baltz <patrick.baltz@sun.com> 1.0-16
- fix pr 13378.  other traffic wasn't being computed when using iptables
  so incorrect log file lines were produced which confused analog.

* Fri Dec 21 2001 Patrick Baltz <patrick.baltz@sun.com> 1.0-15
- add grab_logs.pl that parses log files for specific dates so
  that the download logs button in the UI gives the user something
  reasonable.

* Mon Dec 17 2001 Patrick Baltz <patrick.baltz@sun.com> 1.0-14
- update analog config template so [not listed: ?] doesn't show up in the
  report by file type list

* Sat Dec 15 2001 Patrick Baltz <patrick.baltz@sun.com> 1.0-13
- bump release for changes to sitestats-scripts

* Fri Dec 14 2001 Patrick Baltz <patrick.baltz@sun.com> 1.0-12
- bump release for changes to sitestats-scripts

* Thu Dec 13 2001 Patrick Baltz <patrick.baltz@sun.com> 1.0-11
- update release so it gets into the build

* Tue Nov 13 2001 Will DeHaan <null@sun.com> 1.0-10
- split-logs fix to prevent partial-day logs from being added
  more than once to site logs

* Fri Oct 10 2001 Will DeHaan <null@sun.com> 1.0-9
- separated start from restart in iptables init script

* Tue Aug 7 2001 Will DeHaan <null@sun.com> 1.0-5
- added distributed-site paths

* Fri Aug 03 2001 Will DeHaan <null@sun.com> 1.0-3
- Add the purgeOmatic script for deleting old logs
  and consolidating daily to monthly

* Mon Jul 23 2001 Will DeHaan <null@sun.com> 1.0-2
- Port to Sausalito from special-sauce
- Add iptables detection & accounting support

* Thu Dec 21 2000 Patrick Baltz <pbaltz@cobalt.com>
- fully integrate base-sitestats with the sausalito build system

* Fri Jul 28 2000 Patrick Bose <pbose@cobalt.com>
 - Initial creation

%prep


%setup -n sitestats-scripts

%build

%install
make PREFIX=$RPM_BUILD_ROOT install

%post

%clean
rm -rf $RPM_BUILD_ROOT

%files
%dir /var/state/acct
/etc/analog.cfg.tmpl
/etc/cron.hourly/log_traffic
/etc/cron.daily/tmpwatch_sitestats
/etc/cron.daily/sitestats_purgeOmatic.pl
/etc/logrotate.d/sitestats
/etc/logrotate.d/apache
/usr/local/bin/generateGraph.pl
/usr/local/sbin/split_logs
%attr(755,root,root) /usr/local/sbin/maillog2commonlog.pl
%attr(755,root,root) /usr/local/sbin/ftplog2commonlog
%attr(755,root,root) /etc/rc.d/init.d/iptables
%attr(755,root,root) /usr/local/sbin/grab_logs.pl
