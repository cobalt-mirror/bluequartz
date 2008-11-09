Summary: Sun Cobalt product manual
Name: manual-[LOCALE]
Version: [VERSION]
Vendor: Sun Microsystems, Inc.
Release: 1 
Copyright: Sun Microsystems, 2001
Packager: Will DeHaan, eclipse@sun.com 
Group: Base
Source0: manual.tgz

BuildRoot: /tmp/manual
BuildArchitectures: noarch

%description
- Microsoft's FrontPage2000 Server Extensions for Red Hat linux on x86

%changelog
* Thu Jan 18 2000 Will DeHaan <eclipse@sun.com>
- initial skeletal spec

%prep
%setup -n manual-[LOCALE]

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/sausalito/ui/web/base/documentation
cp -ar * $RPM_BUILD_ROOT/usr/sausalito/ui/web/base/documentation
cd /usr/src/redhat/BUILD/manual-[LOCALE]

%post

%clean
rm -rf /tmp/manual

%files
/usr/sausalito/ui/web/base/documentation/manual-[LOCALE].pdf

