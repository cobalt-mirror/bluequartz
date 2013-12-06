Summary: iStat server for Linux
Name: istatd
Version: 0.5.9
Vendor: Project BlueOnyx
Release: 0BX02
License: New BSD
Group: System Environment/Daemons
Source0: istatd.tar.gz
#Source1: istatd.init
#Source2: istat.conf
#Patch0: istatd-Makefile.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildRequires: libxml2-devel

%ifarch64
BuildArch:      x86_64
%endif
%ifarch32
BuildArch:      i386
%endif

%description
iStat Server is a daemon serving statistics to your iStat iPhone application
from Linux & Solaris. iStat collects data such as CPU, memory, network and disk
usage and keeps the history. Once connecting from the iPhone and entering the
lock code this data will be sent to the iPhone and shown in fancy graphs.

%changelog

* Fri Dec 06 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.9-0BX01
- Added /tmp to the monitored partitions as well.

* Fri Dec 06 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.9-0BX01
- Update to latest version.

* Tue Dec 08 2009 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.4-0BQ2
- use License tag instead of Copyright.

* Sun Aug 30 2009 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.4-0BQ1
- build for Blue Quartz

%prep
%setup -n istatd

#%patch0 -p1 -b .makefile

%build
make all
%configure
make

%install
rm -rf $RPM_BUILD_ROOT
cd $RPM_BUILD_DIR/%{name}
make PREFIX=$RPM_BUILD_ROOT install DESTDIR=$RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT/%{_sysconfdir}/rc.d/init.d
install -m 755 $RPM_BUILD_DIR/%{name}/istatd.init $RPM_BUILD_ROOT/%{_sysconfdir}/rc.d/init.d/istatd
install -m 600 $RPM_BUILD_DIR/%{name}/istat.conf $RPM_BUILD_ROOT/%{_sysconfdir}

%post
/sbin/chkconfig --add istatd

%preun
if [ $1 = 0 ]; then
        /sbin/service istatd stop > /dev/null 2>&1
        /sbin/chkconfig --del istatd
fi

%clean
rm -rf $RPM_BUILD_ROOT

%files
%{_sbindir}/istatd
%config %{_sysconfdir}/rc.d/init.d/istatd
%config(noreplace) %{_sysconfdir}/istat.conf
%doc /usr/share/man/man1/istatd.1.gz
%doc /usr/share/man/man5/istat.conf.5.gz
