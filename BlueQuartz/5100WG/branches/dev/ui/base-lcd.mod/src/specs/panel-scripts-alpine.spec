Summary: Sun Cobalt Panel Scripts
Name: panel-scripts-alpine
Version: 1.0
Release: 10
Copyright: Copyright Sun Microsystems, Inc. All rights reserved.
Group: Base
Source: panel-scripts.tar.gz
BuildRoot: /tmp/buildpanel
BuildArchitectures: noarch

%description
This package contains the menu scripts for /etc/lcd.d that make up the LCD
panel menu for a Sun Cobalt Alpine RaQ server.

%changelog
* Tue Oct 02 2001 Patrick Bose <patrick.bose@sun.com>
  - 1.0-3 add exit script for 30POWER.m 
* Thu Aug 23 2001 Patrick Bose <patrick.bose@sun.com>
  - new, based on panel-scripts-monterey

%prep
rm -rf $RPM_BUILD_ROOT

%setup -n src

%build

%install
mkdir -p ${RPM_BUILD_ROOT}/etc/
mkdir -p ${RPM_BUILD_ROOT}/usr/local/sbin/
cp fppasswd.sh ${RPM_BUILD_ROOT}/usr/local/sbin/
cp -r lcd.d ${RPM_BUILD_ROOT}/etc/
cp ${RPM_BUILD_ROOT}/etc/lcd.d/10main.m/20REBOOT.s/10reboot ${RPM_BUILD_ROOT}/etc/lcd.d/10main.m/30POWER.m/20REBOOT.s/.
cp ${RPM_BUILD_ROOT}/etc/lcd.d/10main.m/30POWER_DOWN.s/10off ${RPM_BUILD_ROOT}/etc/lcd.d/10main.m/30POWER.m/30POWER_DOWN.s/.
ln -s /etc/lcd.d/10main.m/30POWER.m/30POWER_DOWN.s/10off ${RPM_BUILD_ROOT}/etc/lcd.d/power_off
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
%dir /etc/lcd.d/10main.m/13AUTOUPDATE.s
/etc/lcd.d/10main.m/13AUTOUPDATE.s/10autoupdate
%attr(0700,-,-) /etc/lcd.d/10main.m/13AUTOUPDATE.s/string
%dir /etc/lcd.d/10main.m/30POWER.m
%dir /etc/lcd.d/10main.m/30POWER.m/20REBOOT.s
/etc/lcd.d/10main.m/30POWER.m/20REBOOT.s/10reboot
%dir /etc/lcd.d/10main.m/30POWER.m/30POWER_DOWN.s
/etc/lcd.d/10main.m/30POWER.m/30POWER_DOWN.s/10off
%dir /etc/lcd.d/10main.m/30POWER.m/999EXIT.s
/etc/lcd.d/10main.m/30POWER.m/999EXIT.s/10exit
%dir /etc/lcd.d/10main.m/35PANEL.m
%dir /etc/lcd.d/10main.m/35PANEL.m/10LOCK_PANEL.s
/etc/lcd.d/10main.m/35PANEL.m/10LOCK_PANEL.s/10lock
%attr(0700,-,-) /etc/lcd.d/10main.m/35PANEL.m/10LOCK_PANEL.s/string
%dir /etc/lcd.d/10main.m/35PANEL.m/20SET_SEQUENCE.s
/etc/lcd.d/10main.m/35PANEL.m/20SET_SEQUENCE.s/10setseq
%attr(0700,-,-) /etc/lcd.d/10main.m/35PANEL.m/20SET_SEQUENCE.s/string
%dir /etc/lcd.d/10main.m/35PANEL.m/999EXIT.s
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/10ENGLISH.s
/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/10ENGLISH.s/10select_english
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/999EXIT.s
/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/999EXIT.s/10exit
%dir /etc/lcd.d/10main.m/90RESET_PASSWORD.s
/etc/lcd.d/10main.m/90RESET_PASSWORD.s/10reset_password
%attr(0700,-,-) /etc/lcd.d/10main.m/90RESET_PASSWORD.s/string
%dir /etc/lcd.d/10main.m/999EXIT.s
/etc/lcd.d/10main.m/999EXIT.s/10exit
/etc/lcd.d/button_list
/etc/lcd.d/reset_password
/etc/lcd.d/power_off
/usr/local/sbin/fppasswd.sh
