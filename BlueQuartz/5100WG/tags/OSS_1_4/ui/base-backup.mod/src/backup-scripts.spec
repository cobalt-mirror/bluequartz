Summary: Perl modules that contain vital backup functionality
Name: backup-scripts
Version: 1.01.1
Release: 6
Copyright: Cobalt Networks 2000
Group: Silly
Source: backup-scripts.tar.gz
BuildRoot: /tmp/backup-scripts
Requires: ncftp

%prep
%setup -n src

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/perl/Backup.pm
/usr/local/sbin/*

%description
This package contains a number of scripts and perl modules that
contain vital functionality for backups.

%changelog
* Wed Sep 13 2000 Jeff Lovell <jlovell@cobalt.com>
- 1.01-3
- Re-worked ftp underpinnings

* Fri Jun 30 2000 Brenda Mula <bmula@cobalt.com>
- initial spec file.

