%define contentdir %{_datadir}/netdata
%if 0%{?suse_version}
%define distro_post %service_add_post netdata.service
%define distro_preun %service_del_preun netdata.service
%define distro_postun %service_del_postun netdata.service
%define distro_buildrequires BuildRequires:\ systemd-rpm-macros
%else
%define distro_post %systemd_post netdata.service
%define distro_preun %systemd_preun netdata.service
%define distro_postun %systemd_postun_with_restart netdata.service
%define distro_buildrequires %{nil}
%endif

# This is temporary and should eventually be resolved. This bypasses
# the default rhel __os_install_post which throws a python compile
# error.
%define __os_install_post %{nil}

#
# Conditional build:
%bcond_without  systemd  # systemd
%bcond_with     nfacct   # build with nfacct plugin

%if 0%{?fedora} || 0%{?rhel} >= 7 || 0%{?suse_version} >= 1140
%else
%undefine	with_systemd
%endif

Summary:	Real-time performance monitoring, done right
Name:		netdata
Version:	1.4.1
Release:	3%{?dist}
License:	GPL v3+
Group:		Applications/System
Source0:	%{name}.tar.gz
URL:		http://my-netdata.io/
%distro_buildrequires
BuildRequires:	pkgconfig
BuildRequires:	xz
BuildRequires:	zlib-devel
BuildRequires:	libuuid-devel
BuildRequires:  curl
BuildRequires:  MySQL-python
BuildRequires:  libuuid-devel
BuildRequires:  PyYAML
BuildRequires:  python-psycopg2

Requires: zlib
Requires: libuuid
Requires: nc
Requires: lm_sensors
Requires: PyYAML
Requires: MySQL-python
Requires: python-psycopg2

# Packages can be found in the EPEL repo
%if %{with nfacct}
BuildRequires:	libmnl-devel
BuildRequires:	libnetfilter_acct-devel
Requires: libmnl
Requires: libnetfilter_acct
%endif

Requires(pre): /usr/sbin/groupadd
Requires(pre): /usr/sbin/useradd

%if %{with systemd}
%if 0%{?suse_version}
%{?systemd_requires}
%else
Requires(preun):  systemd-units
Requires(postun): systemd-units
Requires(post):   systemd-units
%endif
%else
Requires(post):   chkconfig
%endif

%description
netdata is the fastest way to visualize metrics. It is a resource
efficient, highly optimized system for collecting and visualizing any
type of realtime timeseries data, from CPU usage, disk activity, SQL
queries, API calls, web site visitors, etc.

netdata tries to visualize the truth of now, in its greatest detail,
so that you can get insights of what is happening now and what just
happened, on your systems and applications.

%prep
%setup -n netdata

%build
%configure \
	--with-zlib \
	--with-math \
	%{?with_nfacct:--enable-plugin-nfacct} \
	--with-user=netdata
%{__make} %{?_smp_mflags}

%install
rm -rf $RPM_BUILD_ROOT
%{__make} %{?_smp_mflags} DESTDIR=$RPM_BUILD_ROOT install

find $RPM_BUILD_ROOT -name .keep -delete

install -m 644 -p system/netdata.conf $RPM_BUILD_ROOT%{_sysconfdir}/%{name}
install -d $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d
install -m 644 -p system/netdata.logrotate $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/%{name}

%if %{with systemd}
install -d $RPM_BUILD_ROOT%{_unitdir}
install -m 644 -p system/netdata.service $RPM_BUILD_ROOT%{_unitdir}/netdata.service
install -d $RPM_BUILD_ROOT%{_sysconfdir}/admserv/conf.d/
install -m 644 -p blueonyx/el7/zz_netdata.conf $RPM_BUILD_ROOT%{_sysconfdir}/admserv/conf.d/zz_netdata.conf
%else
# install SYSV init stuff
install -d $RPM_BUILD_ROOT/etc/rc.d/init.d
install -m755 system/netdata-init-d \
        $RPM_BUILD_ROOT/etc/rc.d/init.d/netdata
install -d $RPM_BUILD_ROOT%{_sysconfdir}/admserv/conf.d/
install -m 644 -p blueonyx/el6/zz_netdata.conf $RPM_BUILD_ROOT%{_sysconfdir}/admserv/conf.d/zz_netdata.conf
%endif

%if %{with systemd}
%pre
# Add the "netdata" user
/usr/sbin/groupadd -r netdata 2> /dev/null || :
/usr/sbin/useradd -c "netdata" -g netdata \
        -s /sbin/nologin -r -d %{contentdir} netdata 2> /dev/null || :

%post
# Register the netdata service
%distro_post
if [ -f /usr/bin/systemctl ];then
    /usr/bin/systemctl daemon-reload 2> /dev/null || :
    /usr/bin/systemctl enable netdata 2> /dev/null || :
    /usr/bin/systemctl start netdata 2> /dev/null || :
    /usr/bin/systemctl restart admserv 2> /dev/null || :
else
    # Start the netdata service
    /sbin/chkconfig --add netdata
    /sbin/service netdata start 2> /dev/null || :
    /sbin/service admserv restart 2> /dev/null || :
fi
exit 0

%preun
# Only gets run on uninstall (not upgrades)
if [ "$1" = "0" ]; then
    #rm -f /etc/admserv/conf.d/zz_netdata.conf
    if [ -f /usr/bin/systemctl ];then
        /usr/bin/systemctl stop netdata 2> /dev/null || :
        /usr/bin/systemctl disable netdata 2> /dev/null || :
        /usr/bin/systemctl restart admserv 2> /dev/null || :
    else
        /sbin/service netdata stop 2> /dev/null || :
        /sbin/chkconfig --del netdata
        /sbin/service admserv restart 2> /dev/null || :
    fi
fi
exit 0

%distro_preun

%postun
# Only gets run on upgrade (not uninstalls)
if [ $1 != 0 ]; then
    if [ -f /usr/bin/systemctl ];then
        /usr/bin/systemctl condrestart netdata 2> /dev/null || :
    else
        /sbin/service netdata condrestart 2> /dev/null || :
        /sbin/service admserv restart 2> /dev/null || :
    fi
fi
exit 0
%distro_postun
%else
%pre
# Add the "netdata" user
getent group netdata >/dev/null || groupadd -r netdata
getent passwd netdata >/dev/null || \
  useradd -r -g netdata -s /sbin/nologin \
    -d %{contentdir} -c "netdata" netdata
exit 0

%post
# Register the netdata service
/sbin/chkconfig --add netdata
if [ -f /usr/bin/systemctl ];then
    /usr/bin/systemctl enable netdata 2> /dev/null || :
    /usr/bin/systemctl start netdata 2> /dev/null || :
    /usr/bin/systemctl restart admserv 2> /dev/null || :
else
    # Start the netdata service
    /sbin/service netdata start 2> /dev/null || :
    /sbin/service admserv restart 2> /dev/null || :
fi
exit 0

%preun
# Only gets run on uninstall (not upgrades)
if [ "$1" = "0" ]; then
    #rm -f /etc/admserv/conf.d/zz_netdata.conf
    if [ -f /usr/bin/systemctl ];then
        /usr/bin/systemctl stop netdata 2> /dev/null || :
        /usr/bin/systemctl disable netdata 2> /dev/null || :
        /usr/bin/systemctl restart admserv 2> /dev/null || :
    else
        /sbin/service netdata stop 2> /dev/null || :
        /sbin/chkconfig --del netdata
        /sbin/service admserv restart 2> /dev/null || :
    fi
fi
exit 0

%postun
# Only gets run on upgrade (not uninstalls)
if [ $1 != 0 ]; then
    if [ -f /usr/bin/systemctl ];then
        /usr/bin/systemctl condrestart netdata 2> /dev/null || :
    else
        /sbin/service netdata condrestart 2> /dev/null || :
        /sbin/service admserv restart 2> /dev/null || :
    fi
fi
exit 0
%endif

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)

%dir %{_sysconfdir}/%{name}

%config(noreplace) %{_sysconfdir}/%{name}/*.conf
#%config(noreplace) %{_sysconfdir}/%{name}/charts.d/*.conf
%config(noreplace) %{_sysconfdir}/%{name}/health.d/*.conf
#%config(noreplace) %{_sysconfdir}/%{name}/node.d/*.conf
%config(noreplace) %{_sysconfdir}/%{name}/python.d/*.conf
%config(noreplace) %{_sysconfdir}/logrotate.d/%{name}
%config(noreplace) %{_sysconfdir}/admserv/conf.d/zz_netdata.conf

%{_libexecdir}/%{name}
%{_sbindir}/%{name}

%attr(0700,netdata,netdata) %dir %{_localstatedir}/cache/%{name}
%attr(0700,netdata,netdata) %dir %{_localstatedir}/log/%{name}
%attr(0700,netdata,netdata) %dir %{_localstatedir}/lib/%{name}

%dir %{_datadir}/%{name}
%dir %{_sysconfdir}/%{name}/health.d
%dir %{_sysconfdir}/%{name}/python.d

%if %{with systemd}
%{_unitdir}/netdata.service
%else
%{_sysconfdir}/rc.d/init.d/netdata
%endif

# Enforce 0644 for files and 0755 for directories
# for the netdata web directory
%defattr(0644,root,netdata,0755)
%{_datadir}/%{name}/web

%changelog
* Wed Dec 21 2016 Michael Stauber <mstauber@solarspeed.net> - 1.4.0-3
- Post install/uninstall fixes to follow best practices.

* Wed Dec 21 2016 Michael Stauber <mstauber@solarspeed.net> - 1.4.0-2
- Version number bump for release.

* Wed Dec 21 2016 Michael Stauber <mstauber@solarspeed.net> - 1.4.0-1
- Rebuilt for BlueOnyx.

* Tue Oct 4 2016 Costa Tsaousis <costa@tsaousis.gr> - 1.4.0-1
- the fastest netdata ever (with a better look too)!
- improved IoT and containers support!
- alarms improved in almost every way!
- Several more improvements, new features and bugfixes.
* Sun Aug 28 2016 Costa Tsaousis <costa@tsaousis.gr> - 1.3.0-1
- netdata now has health monitoring
- netdata now generates badges
- netdata now has python plugins
- Several more improvements, new features and bugfixes.
* Tue Jul 26 2016 Jason Barnett <J@sonBarnett.com> - 1.2.0-2
- Added support for EL6
- Corrected several Requires statements
- Changed default to build without nfacct
- Removed --docdir from configure
* Mon May 16 2016 Costa Tsaousis <costa@tsaousis.gr> - 1.2.0-1
- netdata is now 30% faster.
- netdata now has a registry (my-netdata menu on the dashboard).
- netdata now monitors Linux containers.
- Several more improvements, new features and bugfixes.
* Wed Apr 20 2016 Costa Tsaousis <costa@tsaousis.gr> - 1.1.0-1
- Several new features (IPv6, SYNPROXY, Users, Users Groups).
- A lot of bug fixes and optimizations.
* Tue Mar 22 2016 Costa Tsaousis <costa@tsaousis.gr> - 1.0.0-1
- First public release.
* Sun Nov 15 2015 Alon Bar-Lev <alonbl@redhat.com> - 0.0.0-1
- Initial add.
