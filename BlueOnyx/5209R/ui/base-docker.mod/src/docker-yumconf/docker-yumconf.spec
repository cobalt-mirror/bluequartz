Name: docker-yumconf
Version: 1.0.0
Release: 0BX02%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: docker-yumconf.tar.gz
BuildRoot: /tmp/docker-yumconf
Summary: Docker-CE YUM configuration file.

%prep
%setup -q -n %{name}
#%setup

%install
rm -rf $RPM_BUILD_ROOT
cd $RPM_BUILD_DIR/%{name}
mkdir -p $RPM_BUILD_ROOT/etc/yum.repos.d
install -m644 etc/yum.repos.d/docker-ce.repo $RPM_BUILD_ROOT/etc/yum.repos.d/docker-ce.repo
mkdir -p $RPM_BUILD_ROOT/usr/sausalito/configs/rpm
install -m644 usr/sausalito/configs/rpm/docker $RPM_BUILD_ROOT/usr/sausalito/configs/rpm/docker

%post

rpm --import /usr/sausalito/configs/rpm/docker

%preun

%clean
rm -rf $RPM_BUILD_ROOT

%files
%config(noreplace) /etc/yum.repos.d/docker-ce.repo
%attr(0644,root,root) /usr/sausalito/configs/rpm/docker

%description
Docker-CE YUM configuration file.

%changelog

* Tue Nov 2017 2018 Michael Stauber <mstauber@solarspeed.net> 1.0.0-0BX02
- Disabled Docker repo by default, so that we get a version that works
  under OpenVZ as well.

* Tue Jul 17 2018 Michael Stauber <mstauber@solarspeed.net> 1.0.0-0BX01
- Initial build.


