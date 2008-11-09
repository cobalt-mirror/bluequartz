# vendor and service name
%define Vendor [VENDOR]
%define Service [SERVICE]
%define RootDir [ROOTDIR]

Summary: Cobalt UI Gallery
Name: %{Vendor}-%{Service}
Vendor: [VENDORNAME]
Version: 0.3.1
Release: 81
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

[FILES_SECTION]

%files 
/usr/sausalito/ui/web/libImage
%attr(755,httpd,root) %dir /usr/sausalito/ui/style
/usr/sausalito/ui/style/*.xml

%changelog
* Mon May 1 2000 Kevin K.M. Chiu <kevin@cobalt.com>
- initial build
