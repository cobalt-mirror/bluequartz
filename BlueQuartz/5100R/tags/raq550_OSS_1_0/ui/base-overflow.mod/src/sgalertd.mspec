##
# $Id: sgalertd.mspec,v 1.9 2001/12/06 20:37:46 ge Exp $
##
Summary: Buffer Overflow Alert alert daemon
Name: sgalertd
Version: ~VERSION~
Release: ~RELEASE~
Source: sgalertd-~VERSION~.tar.gz
BuildRoot: /tmp/sgalertd-~VERSION~
Copyright: 2001 Sun Microsystems, Inc.
Group: Utilities/System
Packager: Ge' Weijers <ge.weijers@sun.com>

%description
Sgalertd receives notifications from Buffer Overflow-protected programs when they detect a stack smashing attack (or bug) and terminate. This notification is e-mailed to a system admin on request.

%changelog

* Mon Aug 13 2001 Ge' Weijers <ge.weijers@sun.com>
- Initial build

%prep
%setup

%build
make all

%install
rm -rf $RPM_BUILD_ROOT
make PREFIX=$RPM_BUILD_ROOT VERSION=~VERSION~ install

%clean
rm -rf $RPM_BUILD_ROOT

#%pre

%preun
/etc/rc.d/init.d/sgalertd stop
/sbin/chkconfig --del sgalertd

%post
/sbin/chkconfig --add sgalertd

%files
#%doc README
#%doc Copyright
%attr(555, root, root) /usr/sbin/sgalertd
%attr(555, root, root) /etc/rc.d/init.d/sgalertd
%attr(555, root, root) /usr/sbin/testoverflow
%attr(555, root, root) /usr/sbin/sglist
%attr(555, root, root) /usr/sbin/sgtest
%attr(555, root, root) /usr/sausalito/swatch/bin/am_sgalertd.sh
