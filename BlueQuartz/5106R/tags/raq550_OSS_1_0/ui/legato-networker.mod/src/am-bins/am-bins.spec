Summary: Scripts used to integrate Legato NetWorker into ActiveMonitor
Name: legato-networker-am
Version: 1.1.1
Release: 5
Copyright: 2001 Sun Microsystems, Inc.  All rights reserved.
Group: Utils
Source: am-bins.tar.gz
BuildRoot: /tmp/am-bins

%prep
rm -rf $RPM_BUILD_ROOT

%setup -n am-bins

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
The scripts necessary to check the current status of the Legato NetWorker client
daemon.  This is called by swatch+cce as part of the ActiveMonitor subsystem.

%changelog
* Thu Jan 24 2002 Byron Servies <byron.servies@sun.com>
- bumped revision to get new version

* Wed May 16 2001 Byron Servies <byron.servies@sun.com>
- initial spec file

