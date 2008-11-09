Summary: just the binary parts for the initscripts
Name: initutils
Version: 1.0.2
Copyright: GPL
Group: Base
Release: 3
Source: initutils.tar.gz
BuildRoot: /tmp/buildinit

%description
This package contains the binaries needed for system initialization.

%changelog
* Thu Feb 28 2002 Joshua Uziel <uzi@sun.com>
- Add the friendly "blinkme" script

* Thu Apr 05 2001 Patrick Baltz <patrick.baltz@sun.com>
- update patch level to avoid bto problems

* Wed Mar 14 2001 Patrick Baltz <patrick.baltz@sun.com>
- tweak spec file files section to allow for man pages being gzipped

* Sat Apr 22 2000 Adrian Sun <asun@cobalt.com>
- split initscripts. initutils just has the binary parts.

* Thu Apr 20 2000 Adrian Sun <asun@cobalt.com>
- check for vendor-release if cobalt-release doesn't exist.

* Mon Apr  3 2000 Duncan Laurie <duncan@cobaltnet.com>
- 5.1-1
- don't redirect output of lcdstart to /dev/null

* Thu Nov 18 1999 Duncan Laurie <duncan@cobaltnet.com>
- 5.0-19
- stop samba from starting by default

* Wed Nov 03 1999 Duncan Laurie <duncan@cobaltnet.com>
- 5.0-19
- raq3-ja pre-alpha

* Mon Oct 11 1999 Duncan Laurie <duncan@cobaltnet.com>
- 5.0-18
- gm+

* Wed Oct 06 1999 Timothy Stonis <tim@cobaltnet.com>
-5.0-17
-Uncommenting Checking disk... 

* Sat Oct 02 1999 Duncan Laurie <duncan@cobaltnet.com>
- 5.0-16
- saturday

* Tue Sep 28 1999 Duncan Laurie <duncan@cobaltnet.com>
- added postgresql script

* Thu Sep 24 1999 Duncan Laurie <duncan@cobaltnet.com>
- added inetd script

* Thu Sep 23 1999 Duncan Laurie <duncan@cobaltnet.com>
- add bwmgmt script to apply bandwidth limits on startup

* Tue Sep 14 1999 Adrian Sun <asun@cobaltnet.com>
- clock environment set to UTC

* Wed Sep 14 1999 Duncan Laurie <duncan@cobaltnet.com>
- 5.0-10
- garden of the insane

* Sat Sep 09 1999 Duncan Laurie <duncan@cobaltnet.com>
- 5.0-9
- ketchup

* Sat Sep 04 1999 Adrian Sun <asun@cobaltnet.com>
- added transparent storage script.

* Tue Aug 24 1999 Duncan Laurie <duncan@cobaltnet.com>
- meta hack

* Mon Aug 16 1999 Tim Hockin <thockin@cobaltnet.com>
- added rc.clock 

* Tue Aug 10 1999 Duncan Laurie <duncan@cobaltnet.com>
- 5.0-5
- fix typos

* Wed Jul 07 1999 Duncan Laurie <duncan@cobaltnet.com>
- 5.0-1
- new high version number so redhat6 rpms don't complain
- use /etc/cobalt/locale instead of /etc/LANGUAGE

* Fri Jul 02 1999 Duncan Laurie <duncan@cobaltnet.com>
- 2.0-3
- chkconfig friendly initscripts
- showip is not in this package anymore

* Wed Jun 30 1999 Duncan Laurie <duncan@cobaltnet.com>
- 2.0-2
- fix many bad message references
- bring rc scripts closer to redhat6 functionality

* Tue Jun 29 1999 Duncan Laurie <duncan@cobaltnet.com>
- 2.0-1
- initial creation of this spec file
- initscripts are now in CVS
- initscripts now use gettext to retrieve messages

%prep

%setup -n initutils

%build
make CFLAGS="$RPM_OPT_FLAGS" 

%install
rm -rf $RPM_BUILD_ROOT

make PREFIX=$RPM_BUILD_ROOT install
mkdir -p $RPM_BUILD_ROOT/var/run/netreport
chown -R root.root $RPM_BUILD_ROOT

%clean
rm -rf $RPM_BUILD_ROOT

%files
%dir /var/run/netreport
%attr(755,root,root) /bin/doexec
%attr(755,root,root) /bin/usleep
%attr(755,root,root) /usr/sbin/usernetctl
%attr(700,root,root) /usr/sbin/blinkme
%attr(755,root,root) /sbin/netreport
%doc /usr/man/man1/doexec.1*
%doc /usr/man/man1/usleep.1*
%doc /usr/man/man1/usernetctl.1*
%doc /usr/man/man1/netreport.1*
