Name: base-docker-icon
Version: 1.0.0
Release: 0BX01%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-docker-icon.tar.gz
BuildRoot: /tmp/docker-yumconf
Summary: Docker Icon for BlueOnyx GUI

%prep
%setup -q -n %{name}
#%setup

%install
rm -rf $RPM_BUILD_ROOT
cd $RPM_BUILD_DIR/%{name}
mkdir -p $RPM_BUILD_ROOT/usr/sausalito/ui/web/.adm/images/icons/small/white
mkdir -p $RPM_BUILD_ROOT/usr/sausalito/ui/web/.adm/images/icons/small/grey
install -m644 usr/sausalito/ui/web/.adm/images/icons/small/white/docker.png $RPM_BUILD_ROOT/usr/sausalito/ui/web/.adm/images/icons/small/white/docker.png
install -m644 usr/sausalito/ui/web/.adm/images/icons/small/grey/docker.png $RPM_BUILD_ROOT/usr/sausalito/ui/web/.adm/images/icons/small/grey/docker.png

%post

%preun

%clean
rm -rf $RPM_BUILD_ROOT

%files
%attr(0644,root,root) /usr/sausalito/ui/web/.adm/images/icons/small/white/docker.png
%attr(0644,root,root) /usr/sausalito/ui/web/.adm/images/icons/small/grey/docker.png

%description
Docker Icon for BlueOnyx GUI

%changelog

* Tue Jul 17 2018 Michael Stauber <mstauber@solarspeed.net> 1.0.0-0BX01
- Initial build.


