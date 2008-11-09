Summary: Miscellaneous parts of base-time.mod.
Name: base-time-src
Vendor: cobalt
Version: 1.0.1
Release: 2
Copyright: Cobalt Networks, Inc.
Group: CCE/time
Source: base-time-src.tar.gz
BuildRoot: /var/tmp/base-time-src
BuildArchitectures: i386

%description
This builds the src directory for base-time.

%prep
%setup -n src

%build
make

%install
rm -rf $RPM_BUILD_ROOT
PREFIX=$RPM_BUILD_ROOT make install

%files
%defattr(-,root,root)
%attr(0755,root,root)/usr/sausalito/sbin/setTime
%attr(0700,root,root)/usr/sausalito/sbin/epochdate

%changelog
* Sun Sep 10 2000 Patrick Baltz <pbaltz@cobalt.com>
- spec file for src part of base-time
