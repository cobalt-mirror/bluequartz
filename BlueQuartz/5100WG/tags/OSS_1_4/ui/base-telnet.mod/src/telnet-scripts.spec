Summary: Perl modules that contain vital telnet access functionality
Name: telnet-scripts
Version: 1.01.1
Release: 3
Copyright: Cobalt Networks 2000
Group: Silly
Source: telnet-scripts.tar.gz
BuildRoot: /tmp/telnet-scripts

%prep
%setup -n src

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/perl/TelnetAccess.pm
/usr/sausalito/sbin/telnetAccess.pl
/usr/sausalito/sbin/initTelnet.sh

%description
This package contains a number of scripts and perl modules that
contain vital functionality for telnet access.

%changelog
* Mon Oct 02 2000 Patrick Baltz <pbaltz@cobalt.com>
- just adding this comment to see if bto problems are fixed

* Tue Sep 5 2000 Phil Ploquin <pploquin@cobalt.com>
- initial spec file.

