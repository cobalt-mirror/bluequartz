Name: docker-compose
Version: 1.22.0
Release: 1%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: docker-compose.tar.gz
BuildRoot: /tmp/docker-compose
Summary: Docker Compose

%prep
%setup -q -n %{name}
#%setup

%install
rm -rf $RPM_BUILD_ROOT
cd $RPM_BUILD_DIR/%{name}
mkdir -p $RPM_BUILD_ROOT/usr/local/bin/
install -m755 %{name}/docker-compose $RPM_BUILD_ROOT/usr/local/bin/docker-compose

%post

%preun

%clean
rm -rf $RPM_BUILD_ROOT

%files
%attr(0755,root,root) /usr/local/bin/docker-compose

%description
Docker Compose

%changelog

* Sun Oct 21 2018 Michael Stauber <mstauber@solarspeed.net> 1.22.0-1
- Initial build.
