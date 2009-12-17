%define _unpackaged_files_terminate_build 0 
%define _enable_debug_packages 0
%define nutmon_user nutmon
%define nutmon_uid 57
%define nutmon_gid 57

Summary: Network UPS Tools
Name: nut
Version: 2.4.1
Release: 1BLU
License: GPL
Group: System/Applications
Packager: Rickard Osser <rickard.osser@bluapp.com>
Source: http://www.networkupstools.org/source/2.4/%{name}-%{version}.tar.gz
BuildRoot: /var/tmp/%{name}-%{version}-root
Obsoletes: nut-client


%Description
These programs are part of a developing project to monitor the assortment
of UPSes that are found out there in the field. Many models have serial
ports of some kind that allow some form of state checking. This
capability has been harnessed where possible to allow for safe shutdowns,
live status tracking on web pages, and more.

%package client
Group: Applications/System
Summary: Network UPS Tools client monitoring utilities

%description client
This package includes the client utilities that are required to monitor a
ups that the client host has access to, but where the UPS is physically
attached to a different computer on the network.

%prep
%setup -n %{name}-%{version}

%build
./configure --prefix=/usr --sysconfdir=/etc/ups --datadir=/usr/share/nut --with-udev-dir=/etc/udev
make

# fix old enconding manpages
mv man/upscode2.8 man/upscode2.8.iso
iconv -f ISO8859-1 -t UTF-8 -o man/upscode2.8 man/upscode2.8.iso 
mv man/bcmxcp.8 man/bcmxcp.8.iso
iconv -f ISO8859-1 -t UTF-8 -o man/bcmxcp.8 man/bcmxcp.8.iso 
mv man/bcmxcp_usb.8 man/bcmxcp_usb.8.iso
iconv -f ISO8859-1 -t UTF-8 -o man/bcmxcp_usb.8 man/bcmxcp_usb.8.iso 
rm -f man/*.iso

%install
rm -rf ${RPM_BUILD_ROOT}
mkdir -p %{buildroot}%{modeldir} \
         %{buildroot}%{_sysconfdir}/sysconfig \
         %{buildroot}%{_sysconfdir}/udev/rules.d \
         %{buildroot}/var/state/ups \
         %{buildroot}%{_localstatedir}/lib/ups \
         %{buildroot}/etc/init.d \
         %{buildroot}%{_libexecdir} \
         %{buildroot}%{_datadir}/hal/fdi/information/20thirdparty

make install DESTDIR=$RPM_BUILD_ROOT

for file in %{buildroot}%{_sysconfdir}/ups/*.sample
do
   mv $file %{buildroot}%{_sysconfdir}/ups/`basename $file .sample`
done

install -m 755 scripts/Bluapp/ups %{buildroot}%{_sysconfdir}/sysconfig/ups
install -m 755 scripts/Bluapp/upsd %{buildroot}/etc/init.d/ups

rm -f %{buildroot}%{_libdir}/*.la

%pre 
/usr/sbin/useradd -c "Network UPS Tools" -u %{nutmon_uid} -G uucp,nobody \
        -s /bin/false -r -d %{_localstatedir}/lib/ups %{nutmon_user} 2> /dev/null || :
exit 0
%post 
/sbin/chkconfig --add ups
/sbin/ldconfig
exit 0

%preun 
if [ "$1" = "0" ]; then
    /sbin/service ups stop > /dev/null 2>&1
    /sbin/chkconfig --del ups
fi
/sbin/ldconfig
exit 0

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root)
%doc COPYING ChangeLog AUTHORS MAINTAINERS README docs UPGRADING INSTALL NEWS
%dir %attr(750,%{nutmon_user},%{nutmon_user}) %{_sysconfdir}/ups
%dir %attr(750,%{nutmon_user},%{nutmon_user}) %{_localstatedir}/lib/ups
%dir %attr(750,%{nutmon_user},%{nutmon_user}) /var/state/ups
%config(noreplace) %attr(640,%{nutmon_user},%{nutmon_user}) %{_sysconfdir}/ups/nut.conf
%config(noreplace) %attr(640,%{nutmon_user},%{nutmon_user}) %{_sysconfdir}/ups/ups.conf
%config(noreplace) %attr(640,%{nutmon_user},%{nutmon_user}) %{_sysconfdir}/ups/upsd.conf
%config(noreplace) %attr(640,%{nutmon_user},%{nutmon_user}) %{_sysconfdir}/ups/upsd.users
%config(noreplace) %attr(644,root,root) %{_sysconfdir}/sysconfig/ups
%config(noreplace) %attr(640,%{nutmon_user},%{nutmon_user}) %{_sysconfdir}/ups/upsmon.conf
%config(noreplace) %attr(640,%{nutmon_user},%{nutmon_user}) %{_sysconfdir}/ups/upssched.conf
%attr(755,root,root) %{_sysconfdir}/init.d/ups
%{_sysconfdir}/udev/rules.d/52-nut-usbups.rules
%{_bindir}/apcsmart
%{_bindir}/bcmxcp
%{_bindir}/bcmxcp_usb
%{_bindir}/belkin
%{_bindir}/belkinunv
%{_bindir}/bestfcom
%{_bindir}/bestuferrups
%{_bindir}/bestups
%{_bindir}/blazer_ser
%{_bindir}/blazer_usb
%{_bindir}/cyberpower
%{_bindir}/dummy-ups
%{_bindir}/etapro
%{_bindir}/everups
%{_bindir}/gamatronic
%{_bindir}/genericups
%{_bindir}/isbmex
%{_bindir}/liebert
%{_bindir}/masterguard
%{_bindir}/megatec
%{_bindir}/megatec_usb
%{_bindir}/metasys
%{_bindir}/mge-shut
%{_bindir}/mge-utalk
%{_bindir}/microdowell
%{_bindir}/newmge-shut
%{_bindir}/oneac
%{_bindir}/optiups
%{_bindir}/powercom
%{_bindir}/powerpanel
%{_bindir}/rhino
%{_bindir}/richcomm_usb
%{_bindir}/safenet
%{_bindir}/skel
%{_bindir}/snmp-ups
%{_bindir}/solis
%{_bindir}/tripplite
%{_bindir}/tripplite_usb
%{_bindir}/tripplitesu
%{_bindir}/upscode2
%{_bindir}/upsdrvctl
%{_bindir}/upslog
%{_bindir}/usbhid-ups
%{_bindir}/victronups
%{_sbindir}/upsd
%{_datadir}/nut/cmdvartab
%{_datadir}/nut/driver.list
%{_mandir}/man5/ups.conf.5.gz
%{_mandir}/man5/upsd.conf.5.gz
%{_mandir}/man5/upsd.users.5.gz
%{_mandir}/man8/apcsmart.8.gz
%{_mandir}/man8/bcmxcp.8.gz
%{_mandir}/man8/bcmxcp_usb.8.gz
%{_mandir}/man8/belkin.8.gz
%{_mandir}/man8/belkinunv.8.gz
%{_mandir}/man8/bestfcom.8.gz
%{_mandir}/man8/bestuferrups.8.gz
%{_mandir}/man8/bestups.8.gz
%{_mandir}/man8/blazer.8.gz
%{_mandir}/man8/cyberpower.8.gz
%{_mandir}/man8/dummy-ups.8.gz
%{_mandir}/man8/etapro.8.gz
%{_mandir}/man8/everups.8.gz
%{_mandir}/man8/gamatronic.8.gz
%{_mandir}/man8/genericups.8.gz
%{_mandir}/man8/isbmex.8.gz
%{_mandir}/man8/liebert.8.gz
%{_mandir}/man8/masterguard.8.gz
%{_mandir}/man8/megatec.8.gz
%{_mandir}/man8/megatec_usb.8.gz
%{_mandir}/man8/metasys.8.gz
%{_mandir}/man8/mge-shut.8.gz
%{_mandir}/man8/mge-utalk.8.gz
%{_mandir}/man8/microdowell.8.gz
%{_mandir}/man8/nutupsdrv.8.gz
%{_mandir}/man8/oneac.8.gz
%{_mandir}/man8/optiups.8.gz
%{_mandir}/man8/powercom.8.gz
%{_mandir}/man8/powerpanel.8.gz
%{_mandir}/man8/rhino.8.gz
%{_mandir}/man8/richcomm_usb.8.gz
%{_mandir}/man8/safenet.8.gz
%{_mandir}/man8/snmp-ups.8.gz
%{_mandir}/man8/solis.8.gz
%{_mandir}/man8/tripplite.8.gz
%{_mandir}/man8/tripplite_usb.8.gz
%{_mandir}/man8/tripplitesu.8.gz
%{_mandir}/man8/upscode2.8.gz
%{_mandir}/man8/upsd.8.gz
%{_mandir}/man8/upsdrvctl.8.gz
%{_mandir}/man8/usbhid-ups.8.gz
%{_mandir}/man8/victronups.8.gz
%{_bindir}/upsc
%{_bindir}/upscmd
%{_bindir}/upsrw
%{_sbindir}/upsmon
%{_sbindir}/upssched
%{_bindir}/upssched-cmd
%{_libdir}/libupsclient.so*
%{_mandir}/man5/upsmon.conf.5.gz
%{_mandir}/man5/upssched.conf.5.gz
%{_mandir}/man8/upsc.8.gz
%{_mandir}/man8/upscmd.8.gz
%{_mandir}/man8/upsrw.8.gz
%{_mandir}/man8/upslog.8.gz
%{_mandir}/man8/upsmon.8.gz
%{_mandir}/man8/upssched.8.gz

%description
This package contains a number of binaries and scripts used by the Active
Monitor subsystem to monitor services provided by the base-ups module.

%changelog
* Mon Apr 30 2001 Joshua Uziel <uzi@sun.com>
- initial spec file

