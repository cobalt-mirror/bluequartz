Summary: Scripts used to integrate SNMP into ActiveMonitor
Name: base-snmp-am
Version: 1.0.1
Release: 5
Copyright: 2001 Sun Microsystems, Inc.  All rights reserved.
Group: Utils
Source: base-snmp-am.tar.gz
BuildRoot: /tmp/base-snmp-am

%prep
%setup -n base-snmp-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
The scripts necessary to check the current status of the SNMP daemon.  
This is called by swatch+cce as part of the ActiveMonitor subsystem.

%changelog
* Wed Jun 13 2001 James Cheng <james.y.cheng@sun.com>
- initial spec file

