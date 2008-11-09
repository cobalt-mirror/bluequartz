Summary: Perl modules that contain vital webstats functionality
Name: webstats-scripts
Version: 1.01.1
Release: 1
Copyright: Cobalt Networks 2000
Group: Silly
Source: webstats-scripts.tar.gz
BuildRoot: /tmp/webstats-scripts

%prep
%setup -n src

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/perl/WebLogParser.pm
/usr/sausalito/sbin/parseWebLog.pl
/usr/sausalito/sbin/reset_web_stats

%description
This package contains a number of scripts and perl modules that
contain vital functionality for webstats.

%changelog
* Wed Aug 02 2000 Phil Ploquin <pploquin@cobalt.com>
- initial spec file.

