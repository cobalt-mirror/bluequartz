Summary: Cobalt Panel Scripts
Name: panel-scripts-bto
Version: 1.0
Release: 5
Copyright: Cobalt Networks 1999 
Group: Base
Source: panel-scripts.tar.gz
BuildRoot: /tmp/buildpanel
BuildArchitectures: mips mipsel i386

%description
This package contains the menu scripts for /etc/lcd.d that make up the LCD
panel menu for a build to order server

%changelog
* Mon Dec 13 1999 Lyle Scheer <lyle@cobalt.com>
	- added HWBTOS burnin scripts

* Sun May 9 1999 Lyle Scheer <lyle@cobaltnet.com>
	- new

%prep
%setup -n src

%build

%install
mkdir -p ${RPM_BUILD_ROOT}/etc/
cp -r lcd.d ${RPM_BUILD_ROOT}/etc/

%clean
rm -rf ${RPM_BUILD_ROOT}

%files
%dir /etc/lcd.d
%dir /etc/lcd.d/10main.m
%dir /etc/lcd.d/10main.m/10SETUP_NETWORK.m
%dir /etc/lcd.d/10main.m/10SETUP_NETWORK.m/10SETUP_NET1.m
%dir /etc/lcd.d/10main.m/10SETUP_NETWORK.m/10SETUP_NET1.m/10CONFIGURE.s
/etc/lcd.d/10main.m/10SETUP_NETWORK.m/10SETUP_NET1.m/10CONFIGURE.s/10bto_setup
%dir /etc/lcd.d/10main.m/20REBOOT.s
/etc/lcd.d/10main.m/20REBOOT.s/10reboot
%dir /etc/lcd.d/10main.m/30POWER_DOWN.s
/etc/lcd.d/10main.m/30POWER_DOWN.s/10off
%dir /etc/lcd.d/10main.m/40RESET_NETWORK.s
/etc/lcd.d/10main.m/40RESET_NETWORK.s/10reset_net
%dir /etc/lcd.d/10main.m/49SETUP_BTOS.m
%dir /etc/lcd.d/10main.m/49SETUP_BTOS.m/10BOARD_TESTER.s
/etc/lcd.d/10main.m/49SETUP_BTOS.m/10BOARD_TESTER.s/10setup_tester
%dir /etc/lcd.d/10main.m/49SETUP_BTOS.m/20SYSTEM_TESTER.s
/etc/lcd.d/10main.m/49SETUP_BTOS.m/20SYSTEM_TESTER.s/10setup_tester
%dir /etc/lcd.d/10main.m/49SETUP_BTOS.m/30EXPERIMENTAL.s
/etc/lcd.d/10main.m/49SETUP_BTOS.m/30EXPERIMENTAL.s/10setup_tester
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/10ENGLISH.s
/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/10ENGLISH.s/10select_english
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/20JAPANESE.s
/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/20JAPANESE.s/10select_japanese
%dir /etc/lcd.d/10main.m/999EXIT.s
/etc/lcd.d/10main.m/999EXIT.s/10exit
/etc/lcd.d/button_list
