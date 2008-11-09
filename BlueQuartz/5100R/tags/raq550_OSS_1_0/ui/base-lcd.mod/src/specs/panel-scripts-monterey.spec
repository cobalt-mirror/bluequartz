Summary: Cobalt Panel Scripts
Name: panel-scripts-monterey
Version: 1.1
Release: 1
Copyright: Cobalt Networks 2000
Group: Base
Source: panel-scripts.tar.gz
BuildRoot: /tmp/buildpanel
BuildArchitectures: noarch

%description
This package contains the menu scripts for /etc/lcd.d that make up the LCD
panel menu for a Cobalt Pacifica Raq server.

%changelog
* Mon Feb 26 2001 Patrick Bose <pbose@cobalt.com>
- Sausalito for Raq panel utils
- incorporated changes for i2c front panel
- consolidating lcdstart/setup network into ipnmgw 

* Fri Dec 01 2000 Timothy Stonis <tim@cobalt.com>
 - Add locking

* Thu Nov 16 2000 Patrick Bose <pbose@cobalt.com>
	- new, based on panel-scripts-pacifica
  - making reset_password a menu option (not by reset button)
  - making powerdown script work from power down button

%prep
rm -rf $RPM_BUILD_ROOT

%setup -n src

%build

%install
mkdir -p ${RPM_BUILD_ROOT}/etc/
mkdir -p ${RPM_BUILD_ROOT}/usr/local/sbin/
cp fppasswd.sh ${RPM_BUILD_ROOT}/usr/local/sbin/
cp -r lcd.d ${RPM_BUILD_ROOT}/etc/
cp ${RPM_BUILD_ROOT}/etc/lcd.d/10main.m/30POWER_DOWN.s/10off ${RPM_BUILD_ROOT}/etc/lcd.d/power_down
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
%dir /etc/lcd.d/10main.m/20REBOOT.s
/etc/lcd.d/10main.m/20REBOOT.s/10reboot
%dir /etc/lcd.d/10main.m/35PANEL.m
%dir /etc/lcd.d/10main.m/35PANEL.m/10LOCK_PANEL.s
/etc/lcd.d/10main.m/35PANEL.m/10LOCK_PANEL.s/10lock
%dir /etc/lcd.d/10main.m/35PANEL.m/20SET_SEQUENCE.s
/etc/lcd.d/10main.m/35PANEL.m/20SET_SEQUENCE.s/10setseq
%dir /etc/lcd.d/10main.m/35PANEL.m/999EXIT.s
/etc/lcd.d/10main.m/90RESET_PASSWORD.s/10reset_password
%dir /etc/lcd.d/10main.m/999EXIT.s
/etc/lcd.d/10main.m/999EXIT.s/10exit
/etc/lcd.d/button_list
/etc/lcd.d/power_down
/etc/lcd.d/reset_password
/usr/local/sbin/fppasswd.sh
