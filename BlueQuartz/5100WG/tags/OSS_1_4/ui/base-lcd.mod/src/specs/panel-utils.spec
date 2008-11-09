Summary: LCD Front Panel Utility Programs 
Name: panel-utils 
Version: 5.0.1
Copyright: GPL
BuildArchitectures: i386 mips mipsel
Group: Base
Release: 6
Source: panel-utils.tar.gz
BuildRoot: /tmp/buildpanel

%description
This package contains the utility programs used for getting input and
displaying output to the LCD console.

%changelog
* Thu Jul 26 2001 Philip Martin <philip.martin@sun.com>
- add a password widget

* Tue Sep 21 2000 Patrick Bose <pbose@cobalt.com>
- 5.0-5 decrease lcdsleep interval to 30 seconds
- fix syntax error in lcd-showip 

* Tue Sep 19 2000 Patrick Bose <pbose@cobalt.com>
- 5.0-4 silent mode doesn't destroy lockfiles
- lockfile symlink security fixes
- less dependent on cce

* Mon Jul 03 2000 Patrick Bose <pbose@cobalt.com>
- 5.0-2 updates to support configuring eth1

* Mon May 29 2000 Patrick Bose <pbose@cobalt.com>
- 5.0-1
- written for cce

* Sun Apr 30 2000 Duncan Laurie <duncan@cobalt.com>
- 4.2-3
- strip leading whitespace from console menu choices

* Sun Apr 16 2000 Duncan Laurie <duncan@cobalt.com>
- 4.2-2
- don't call unnecessary network.cgi

* Fri Mar 31 2000 Duncan Laurie <duncan@cobalt.com>
- 4.2-1
- serial input for lcd-menu (with option -c)
- serial input for lcdstart

* Thu Dec  2 1999 Duncan Laurie <duncan@cobalt.com>
- 4.1-4
- real fix for lcdstart?

* Mon Nov 08 1999 Adrian Sun <asun@cobalt.com>
- 4.1-3
- fix for lcdstart.

* Wed Nov 03 1999 Duncan Laurie <duncan@cobalt.com>
- 4.1-2
- raq3-ja pre-alpha

* Fri Oct 22 1999 Adrian Sun <asun@cobaltnet.com>
- mondo rewrite and rev.

* Fri Oct 01 1999 Duncan Laurie <duncan@cobaltnet.com>
- remove japanese selection from the language menu

* Sat Sep 18 1999 Adrian Sun <asun@cobaltnet.com>
- make /dev/lcd writeable by the httpd group.

* Fri Sep 17 1999 Adrian Sun <asun@cobaltnet.com>
- fixed lcd-getip to deal with empty arguments.

* Wed Jul 08 1999 Duncan Laurie <duncan@cobaltnet.com>
- get locale info from /etc/cobalt/locale instead of /etc/LANGUAGE

* Tue Jul 07 1999 Timothy Stonis <tim@cobaltnet.com> 
- Initial creation

* Sun May 9 1999 Lyle Scheer <lyle@cobaltnet.com>
        - nearly total rewrite

%prep
rm -rf $RPM_BUILD_ROOT

%setup -n src

%build
make 

%install
make PREFIX=$RPM_BUILD_ROOT install-utils

mkdir -p ${RPM_BUILD_ROOT}/dev
mknod ${RPM_BUILD_ROOT}/dev/lcd c 10 140

%post
/sbin/chkconfig --add lcdsleep.init
/sbin/chkconfig --level 0 lcdsleep.init off
/sbin/chkconfig --level 6 lcdsleep.init off
/sbin/chkconfig --level 3 lcdsleep.init on

%clean
rm -rf $RPM_BUILD_ROOT

%files
%attr (775,root,httpd) /dev/lcd
%attr (755,root,root) /sbin/lcd-write
%attr (755,root,root) /sbin/lcd-swrite
%attr (755,root,root) /sbin/led-write
%attr (755,root,root) /sbin/lcdsleep
%attr (755,root,root) /sbin/lcd-yesno
%attr (755,root,root) /sbin/lcd-getip
%attr (755,root,root) /sbin/lcd-flash
%attr (755,root,root) /sbin/lcd-getpass
%attr (755,root,root) /sbin/readbutton
%attr (755,root,root) /sbin/link
%attr (755,root,root) /sbin/linkstatus
%attr (755,root,root) /sbin/lcd-menu
%attr (755,root,root) /sbin/lcdstop
%attr (755,root,root) /sbin/lcdstart
%attr (755,root,root) /sbin/ruleflush
%attr(644,root,root) /usr/sausalito/perl/LCD.pm
%config %attr (755,root,root) /etc/rc.d/init.d/lcdsleep.init
%config %attr (755,root,root) /etc/rc.d/init.d/lcd-showip


