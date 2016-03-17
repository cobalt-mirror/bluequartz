#
# Spec file for blueonyx-letsencrypt
#

%define pkgname blueonyx-letsencrypt
%define instdir letsencrypt

Name:           %{pkgname}
Version:        0.1.0
Release:        2
Packager:       Michael Stauber <mstauber@blueonyx.it>
Vendor:         Let's Encrypt
URL:            http://www.letsencrypt.org
License:        Apache Version 2.0
Group:          System Environment/BlueOnyx
BuildRoot:      %{_tmppath}/%{name}-%{version}-root
BuildArch:      noarch
Distribution:   BlueOnyx
Source:         %{name}.tar.gz
Requires:       python-devel
Requires:       python-virtualenv
Requires:       gcc
Requires:       dialog
Requires:       augeas-libs
Requires:       git
Requires:       libffi-devel
Requires:       redhat-rpm-config
Summary:        Let's Encrypt Python Client

%description
Let's Encrypt Python Client

%prep
%setup -n %{name}

%build

%install

%{__rm} -rf %{buildroot}
mkdir %{buildroot}/
mv * %{buildroot}/

mkdir -p $RPM_BUILD_ROOT/usr/sausalito/
ls -la $RPM_BUILD_ROOT
rm -f $RPM_BUILD_ROOT/${name}.spec
mv $RPM_BUILD_ROOT/%{instdir} $RPM_BUILD_ROOT/usr/sausalito/
ls -la $RPM_BUILD_ROOT/usr/sausalito/

#
# Generate file list:
#

# Toplevel directory for clean uninstalls:
echo "/usr/sausalito/%{instdir}" > solFile.list

# Find all files that get default permissions:
find $RPM_BUILD_ROOT/ -type f -print | sed "s@^$RPM_BUILD_ROOT@@g" | grep -v ${name}.spec | grep -v "\.packlist" | grep -v "\.svn" |grep -v "\.pl$" >> solFile.list

# Find all Scripts and make them executeable:
find $RPM_BUILD_ROOT/ -type f -print | sed "s@^$RPM_BUILD_ROOT@@g" | grep -v ${name}.spec | grep -v "\.packlist" | grep -v "Makefile\.pl"| grep -v "\.svn" | egrep "(\.init|\.pl|\.sh|\.cgi|\.pm)$" >> solFileEXEC.list

# Change attributes for all executeables:
/bin/sed -i -e 's@^@%attr(755,root,root) @g' solFileEXEC.list

# Merge the fileLists:
cat solFileEXEC.list >> solFile.list

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

%changelog

* Fri Dec 04 2015 Michael Stauber <mstauber@blueonyx.it>
- [0.1.0-2] Updated Requirements.

* Fri Dec 04 2015 Michael Stauber <mstauber@blueonyx.it>
- [0.1.0-1] Initial build.

