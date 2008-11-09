Summary: Perl modules that contain useful utility functions for handlers.
Name: perl-handler-utils
Version: 1.01.1
Release: 3
Copyright: Cobalt Networks 2000
Group: Silly
Source: perl-handler-utils.tar.gz
BuildRoot: /tmp/perl-sauce

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
/usr/sausalito/perl/Sauce/Util/SecurityLevels.pm

%description
This package contains a number of perl modules that contain useful
utility functions for writing cced event handler scripts.

%changelog
* Tue May 29 2001 Mike Waychison <michael.waychison@sun.com>
- Added Security levels to the Sauce::Util package

* Sun Apr 29 2000 Jonathan Mayer <jmayer@cobalt.com>
- initial spec file

