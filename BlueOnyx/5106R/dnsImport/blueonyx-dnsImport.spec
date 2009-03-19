Name:           blueonyx-dnsImport
Version: 	2.1
Release: 	3%{?dist}
Packager:       'Project BlueOnyx'
Vendor:         'Project BlueOnyx'
URL:            http://www.blueonyx.it
License:        Sun modified BSD
Group:          System
BuildRoot:      %{_tmppath}/%{name}-root
BuildArch:      i386
Distribution:   BlueOnyx
Source:         %{name}.tar.gz
Summary:        nuonce-dnsImport repackaged for BlueOnyx
Requires:       perl
Obsoletes:	nuonce-dnsImport
AutoReq         : yes
AutoProv        : yes

%description
nuonce-dnsImport repackaged and modified for BlueOnyx

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

* Thu Mar 19 2009 Michael Stauber <mstauber@solarspeed.net>
- [2.1-3:] New package name
- Added obsoletion of nuonce-dnsImport


* Sat Mar 01 2008 Michael Stauber <mstauber@solarspeed.net>
- [2.1-2:] Added venet support for virtual BlueQuartz

