Name:           blueonyx-dnstoolbox
Version: 	1.0.5
Release: 	1%{?dist}
Packager:       'Project BlueOnyx'
Vendor:         'Project BlueOnyx'
URL:            http://www.blueonyx.it
License:        Sun modified BSD
Group:          System
BuildRoot:      %{_tmppath}/%{name}-root
BuildArch:      i386
Distribution:   BlueOnyx
Source:         %{name}.tar.gz
Summary:        Shell tools for importing, deleting and modyfing DNS.
Requires:       perl
Obsoletes:	nuonce-dnsImport
Obsoletes:	blueonyx-dnsImport
AutoReq         : yes
AutoProv        : yes

%description
nuonce-dnsImport repackaged and modified for BlueOnyx and extended with
functionality to delete and modify DNS records.

%prep
%setup -q -n %{name}

%build

rm -R -f $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT
mv * $RPM_BUILD_ROOT/

%install
  [ -x /usr/lib/rpm/brp-compress ] && /usr/lib/rpm/brp-compress

# Symlinks:
# Recreates source tarball if need be - with dereferenced symlinks.

    find $RPM_BUILD_ROOT/ -type l -print | \
    sed "s@^$RPM_BUILD_ROOT@@g" > solSymLinks.list
    if [ "$(cat solSymLinks.list)X" = "X" ] ; then
        pwd
    fi

# Files:

  find $RPM_BUILD_ROOT/ -type f -print | \
    sed "s@^$RPM_BUILD_ROOT@@g" | \
    grep -v perllocal.pod | \
    grep -v "\.packlist" > solFile.list

  if [ "$(cat solFile.list)X" = "X" ] ; then
    echo "ERROR: EMPTY FILE LIST"
    exit 1
  fi

%pre

%post

%preun

%postun

%clean
rm -R -f $RPM_BUILD_ROOT

%files -f solFile.list
%defattr(-,root,root)

%changelog

* Fri Jul 17 2009 Michael Stauber <mstauber@solarspeed.net> [1.0.5-1]
- Modified blueonyx-dnstoolbox/usr/sausalito/sbin/dnsDeleteAllRecords.pl
- Removed erronous exit statements.

* Tue May 12 2009 Michael Stauber <mstauber@solarspeed.net> [1.0.4-1] 
- Modified blueonyx-dnstoolbox/usr/sausalito/sbin/dnsImport.pl
- Added changes submitted by Stephanie Sullivan. Purposes:
- 1) to correctly import txt records (not previously handled)
- 2) to try to open the include files and import any records it understands
- 3) Fixed cut and paste error pri was not stripped off correctly from imported zone files

* Fri Mar 20 2009 Michael Stauber <mstauber@solarspeed.net> [1.0.3-1] 
- Small modification to text output from blueonyx-dnstoolbox/usr/sausalito/sbin/dnsDeleteAllRecords.pl

* Thu Mar 19 2009 Michael Stauber <mstauber@solarspeed.net> [1.0.2-1] 
- Added blueonyx-dnstoolbox/usr/sausalito/sbin/dnsMassChange.pl
- Added blueonyx-dnstoolbox/usr/sausalito/sbin/dnsMXmassChange.pl

* Thu Mar 19 2009 Michael Stauber <mstauber@solarspeed.net> [1.0.1-1] 
- Modified blueonyx-dnstoolbox/usr/sausalito/sbin/dnsImport.pl
- Added Rickard Osser's 2ndary DNS modifications.

* Thu Mar 19 2009 Michael Stauber <mstauber@solarspeed.net> [1.0.0-1] 
- New build

