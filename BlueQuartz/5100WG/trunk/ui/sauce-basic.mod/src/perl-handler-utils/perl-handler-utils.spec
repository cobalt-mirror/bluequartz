Summary: Perl modules that contain useful utility functions for handlers.
Name: perl-handler-utils
Version: 1.01.1
Release: 3BQ2%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueQuartz
Source: perl-handler-utils.tar.gz
BuildRoot: /tmp/perl-sauce
Provides: perl(Sauce::Util::SecurityLevels)

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
* Mon Jul 10 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 1.01.1-3BQ2
- build for BlueQuartz 5100WG.

* Thu Sep 18 2003 Hisao SHIBUYA <shibuya@alpha.or.jp>
- (1.01.1-30Q2)
- implement to handle xinetd config file on Service.pm 

* Tue Jul 22 2003 Hisao SHIBUYA <shibuya@alpha.or.jp>
- (1.01.1-3OQ1)
- provide perl(Sauce::Util::SecurityLevels)

* Tue May 29 2001 Mike Waychison <michael.waychison@sun.com>
- Added Security levels to the Sauce::Util package

* Sun Apr 29 2000 Jonathan Mayer <jmayer@cobalt.com>
- initial spec file

