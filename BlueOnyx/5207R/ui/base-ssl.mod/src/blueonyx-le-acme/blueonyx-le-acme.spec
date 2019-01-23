#
# Spec file for blueonyx-le-acme
#

%define pkgname blueonyx-le-acme
%define instdir acme

Name:           %{pkgname}
Version:        2.8.0
Release:        3
Packager:       Michael Stauber <mstauber@blueonyx.it>
Vendor:         Neil Pang
URL:            https://github.com/Neilpang/acme.sh
License:        GPLv3
Group:          System Environment/BlueOnyx
BuildRoot:      %{_tmppath}/%{name}-%{version}-root
BuildArch:      noarch
Distribution:   BlueOnyx
Source:         %{name}.tar.gz
#Requires:       socat
Requires:       git
Requires:       redhat-rpm-config
Obsoletes:	blueonyx-letsencrypt
Summary:        A pure Unix shell script implementing ACME client protocol

%description
A pure Unix shell script implementing ACME client protocol https://acme.sh

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

CBASH=$(cat /root/.bashrc |grep /usr/sausalito/acme|wc -l)
if [ "$CBASH" -eq "0" ];then
     echo ". /usr/sausalito/acme/acme.sh.env" >> /root/.bashrc
fi

CSHRC=$(cat /root/.cshrc |grep /usr/sausalito/acme|wc -l)
if [ "$CSHRC" -eq "0" ];then
     echo "source /usr/sausalito/acme/acme.sh.csh" >> /root/.cshrc
fi

CTCSH=$(cat /root/.tcshrc |grep /usr/sausalito/acme|wc -l)
if [ "$CTCSH" -eq "0" ];then
     echo "source /usr/sausalito/acme/acme.sh.csh" >> /root/.tcshrc
fi

CCRON=$(crontab -l|grep /usr/sausalito/acme/acme.sh|wc -l)
if [ "$CCRON" -eq "0" ];then
     crontab /usr/sausalito/acme/crontab.cron
fi

%preun
#/usr/sausalito/acme/acme.sh --uninstall

%postun

%clean
rm -R -f $RPM_BUILD_ROOT

%files -f solFile.list

%changelog

* Wed Jan 23 2019 Michael Stauber <mstauber@blueonyx.it>
- [2.8.0-3] More EL6 related fixed and addition of acme_wrapper.sh
  Also fixes to acme.sh itself to chmod written files to 644.

* Wed Jan 23 2019 Michael Stauber <mstauber@blueonyx.it>
- [2.8.0-2] EL6 uses /bin/bash and not /usr/bin/bash
- Removed socat requirement

* Tue Jan 22 2019 Michael Stauber <mstauber@blueonyx.it>
- [2.8.0-1] Initial build.
