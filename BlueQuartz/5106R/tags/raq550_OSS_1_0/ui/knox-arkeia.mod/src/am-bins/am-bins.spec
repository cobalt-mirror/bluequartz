Summary: Scripts used to integrate Knox Arkeia into ActiveMonitor
Name: knox-arkeia-am
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
The scripts necessary to check the current status of the Knox Arkeia client
daemon.  This is called by swatch+cce as part of the ActiveMonitor subsystem.

%changelog
* Wed May 16 2001 Byron Servies <byron.servies@sun.com>
- Updates from code review: fixed summary and description.

* Mon May 14 2001 Byron Servies <byron.servies@sun.com>
- initial spec file

