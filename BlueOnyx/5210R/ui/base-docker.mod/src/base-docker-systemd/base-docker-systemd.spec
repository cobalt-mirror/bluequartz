Name: base-docker-systemd
Version: 1.0.0
Release: 1%{dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: base-docker-systemd.tar.gz
BuildRoot: /tmp/base-docker-systemd
Summary: Systemd related Docker CT scripts

%prep
%setup -q -n %{name}
#%setup

%install
rm -rf $RPM_BUILD_ROOT
cd $RPM_BUILD_DIR/%{name}
mkdir -p $RPM_BUILD_ROOT/usr/sausalito/sbin/
install -m755 %{name}/dockercts.pl $RPM_BUILD_ROOT/usr/sausalito/sbin/dockercts.pl
mkdir -p $RPM_BUILD_ROOT/usr/lib/systemd/system
install -m644 %{name}/dockercts.service $RPM_BUILD_ROOT/usr/lib/systemd/system/dockercts.service

%post

systemctl daemon-reload >/dev/null 2>&1 || :
systemctl enable dockercts.service >/dev/null 2>&1 || :
systemctl restart dockercts.service >/dev/null 2>&1 || :

%preun

systemctl stop dockercts.service >/dev/null 2>&1 || :
systemctl disable dockercts.service >/dev/null 2>&1 || :

%clean
rm -rf $RPM_BUILD_ROOT

%files
%attr(0644,root,root) /usr/lib/systemd/system/dockercts.service
%attr(0755,root,root) /usr/sausalito/sbin/dockercts.pl

%description
Systemd related Docker CT scripts

%changelog

* Sun Oct 21 2018 Michael Stauber <mstauber@solarspeed.net> 1.0.0-1
- Initial build.
