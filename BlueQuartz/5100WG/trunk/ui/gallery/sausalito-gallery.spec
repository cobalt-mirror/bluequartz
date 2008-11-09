# vendor and service name
%define Vendor [VENDOR]
%define Service [SERVICE]
%define RootDir [ROOTDIR]

Summary: Cobalt UI Gallery
Name: %{Vendor}-%{Service}
Vendor: [VENDORNAME]
Version: 0.3.1
Release: 81OQ2
Copyright: Cobalt Networks, Inc.
Group: CCE/%{Service}
Source: %{Vendor}-%{Service}-[VERSION].tar.gz
BuildRoot: /var/tmp/%{Vendor}-%{Service}
[BUILDARCH]

%description
sausalito-gallery has all the style and image bits for the UI

[DESCRIPTION_SECTION]

%prep
%setup 

%build
PREFIX=$RPM_BUILD_ROOT CCETOPDIR=/usr/sausalito make

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/sausalito/ui
PREFIX=$RPM_BUILD_ROOT CCETOPDIR=/usr/sausalito make install 
rm $RPM_BUILD_ROOT/usr/sausalito/ui/style/trueBlue.xml.zh_CN
rm $RPM_BUILD_ROOT/usr/sausalito/ui/style/trueBlue.xml.zh_TW

[FILES_SECTION]

%files 
/usr/sausalito/ui/web/libImage
%attr(755,apache,root) %dir /usr/sausalito/ui/style
/usr/sausalito/ui/style/*.xml

%changelog
* Wed Jul 23 2003 Hisao SHIBUYA <shibuya@alpha.or.jp>
- (0.3.1-81OQ2)
- use apache user for httpdinstead of httpd user.

* Tue Jul 22 2003 Hisao SHIBUYA <shibuya@alpha.or.jp>
- (0.3.1-81OQ1)
- build for RedHat 9.
- delete unpackaged files.

* Mon May 1 2000 Kevin K.M. Chiu <kevin@cobalt.com>
- initial build
