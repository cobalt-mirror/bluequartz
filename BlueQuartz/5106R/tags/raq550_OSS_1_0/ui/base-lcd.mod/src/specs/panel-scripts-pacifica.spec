Summary: Cobalt Panel Scripts
Name: panel-scripts-pacifica
Version: 1.2
Release: 1
Copyright: Cobalt Networks 1999-2001 
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

* Mon Dec 4 2000 Timothy Stonis <tim@cobalt.com>
- Add lcd lock

* Mon Sep 11 2000 Duncan Laurie <duncan@cobalt.com> 1.1-3
- change /tmp/.lcdlock to /etc/locks/.lcdlock

* Sun Apr 30 2000 Duncan Laurie <duncan@cobalt.com>
- 1.1-2
- fixes for reboot/powerdown

* Fri Mar 31 2000 Duncan Laurie <duncan@cobalt.com>
- 1.1-1
- read input from console in setup_network

* Wed Nov 03 1999 Duncan Laurie <duncan@cobalt.com>
- 1.0-8
- raq3-ja pre-alpha

* Mon Oct 04 1999 Adrian Sun <asun@cobaltnet.com>
- gateway fix

* Fri Oct 01 1999 Duncan Laurie <duncan@cobaltnet.com>
- move language choices out into locale module

* Fri Sep 17 1999 Timothy Stonis <tim@cobaltnet.com>

 - Add reset_passwd script

* Tue Jul 6 1999 Timothy Stonis <tim@cobaltnet.com>
	- New for Pacifica

* Sun May 9 1999 Lyle Scheer <lyle@cobaltnet.com>
	- new
%prep
%setup -n src

%build

%install
mkdir -p ${RPM_BUILD_ROOT}/etc/
cp -r lcd.d ${RPM_BUILD_ROOT}/etc/
mkdir -p ${RPM_BUILD_ROOT}/usr/local/sbin/
cp fppasswd.sh ${RPM_BUILD_ROOT}/usr/local/sbin/


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
%dir /etc/lcd.d/10main.m/30POWER_DOWN.s
/etc/lcd.d/10main.m/30POWER_DOWN.s/10off
%dir /etc/lcd.d/10main.m/40PANEL.m
%dir /etc/lcd.d/10main.m/40PANEL.m/10LOCK_PANEL.s
/etc/lcd.d/10main.m/40PANEL.m/10LOCK_PANEL.s/10lock
%dir /etc/lcd.d/10main.m/40PANEL.m/20SET_SEQUENCE.s
/etc/lcd.d/10main.m/40PANEL.m/20SET_SEQUENCE.s/10setseq
%dir /etc/lcd.d/10main.m/40PANEL.m/999EXIT.s
#%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m
#%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/999EXIT.s
#/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/999EXIT.s/10exit
#%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/10ENGLISH.s
#/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/10ENGLISH.s/10select_english
#%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/20JAPANESE.s
#/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/20JAPANESE.s/10select_japanese
%dir /etc/lcd.d/10main.m/999EXIT.s
/etc/lcd.d/10main.m/999EXIT.s/10exit
/etc/lcd.d/button_list
/etc/lcd.d/reset_password
/usr/local/sbin/fppasswd.sh
