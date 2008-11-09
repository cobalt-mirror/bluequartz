Summary: Cobalt Panel Scripts for Sun Cobalt Control Station
Name: panel-scripts-bigdaddy
Version: 1.0.1
Release: 1
Copyright: Sun Microsystems, Inc.
Group: Base
Source: panel-scripts.tar.gz
BuildRoot: /tmp/buildpanel
BuildArchitectures: noarch

%description
This package contains the menu scripts for /etc/lcd.d that make up the LCD
panel menu for a Sun Cobalt Control Station server.

%changelog
* Thu Sep 27 2001 Jeff Lovell <jeffrey.lovell@sun.com>
- bigdaddy spec

%prep
rm -rf $RPM_BUILD_ROOT

%setup -n src

%build

%install
mkdir -p ${RPM_BUILD_ROOT}/etc/
cp -r lcd.d ${RPM_BUILD_ROOT}/etc/
chown -R root.root ${RPM_BUILD_ROOT}/etc/lcd.d
chmod g+s ${RPM_BUILD_ROOT}/etc/lcd.d 
chmod -R 500 ${RPM_BUILD_ROOT}/etc/lcd.d

%clean
rm -rf ${RPM_BUILD_ROOT}

%files
%dir /etc/lcd.d
%dir /etc/lcd.d/10main.m
%dir /etc/lcd.d/10main.m/10SETUP_NETWORK.m
%dir /etc/lcd.d/10main.m/10SETUP_NETWORK.m/10SETUP_NET1.m
%dir /etc/lcd.d/10main.m/10SETUP_NETWORK.m/10SETUP_NET1.m/10CONFIGURE.s
/etc/lcd.d/10main.m/10SETUP_NETWORK.m/10SETUP_NET1.m/10CONFIGURE.s/10ipnmgw
%dir /etc/lcd.d/10main.m/12ADVANCED_NETWORK.m
%dir /etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/10REVIEW_SETTINGS.s
/etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/10REVIEW_SETTINGS.s/10review_settings
%dir /etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/20SETUP_PRIMARY.s
/etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/20SETUP_PRIMARY.s/10setup_primary
%dir /etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/50SETUP_SECONDARY.s
/etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/50SETUP_SECONDARY.s/10setup_secondary
%dir /etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/999EXIT.s
/etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/999EXIT.s/10exit
%dir /etc/lcd.d/10main.m/20REBOOT.s
/etc/lcd.d/10main.m/20REBOOT.s/10reboot
%dir /etc/lcd.d/10main.m/30POWER_DOWN.s
/etc/lcd.d/10main.m/30POWER_DOWN.s/10off
%dir /etc/lcd.d/10main.m/999EXIT.s
/etc/lcd.d/10main.m/999EXIT.s/10exit
/etc/lcd.d/button_list
/etc/lcd.d/reset_password


