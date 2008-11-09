Summary: Cobalt Panel Scripts for Qube
Name: panel-scripts-atherton
Version: 1.0.1
Release: 6
Copyright: Cobalt Networks 1999 
Group: Base
Source: panel-scripts.tar.gz
BuildRoot: /tmp/buildpanel
BuildArchitectures: noarch

%description
This package contains the menu scripts for /etc/lcd.d that make up the LCD
panel menu for a Cobalt Qube server 

%changelog
* Tue Sep 19 2000 Patrick Bose <pbose@cobalt.com>
- 5.0-4 silent mode doesn't destroy lockfiles
- lockfile symlink security fixes
- less dependent on cce

* Sun Jun 02 2000 Patrick Bose <pbose@cobalt.com>
        - adding advanced network menu
* Wed May 10 2000 Patrick Bose <pbose@cobalt.com>
	- new
%prep
rm -rf $RPM_BUILD_ROOT

%setup -n src

%build

%install
mkdir -p ${RPM_BUILD_ROOT}/etc/
mkdir -p ${RPM_BUILD_ROOT}/usr/local/sbin/
cp fppasswd.sh ${RPM_BUILD_ROOT}/usr/local/sbin/
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
%dir /etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/30DHCP_PRIMARY.s
/etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/30DHCP_PRIMARY.s/10dhcp_primary
%dir /etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/50SETUP_SECONDARY.s
/etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/50SETUP_SECONDARY.s/10setup_secondary
%dir /etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/60DHCP_SECONDARY.s
/etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/60DHCP_SECONDARY.s/10dhcp_secondary
%dir /etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/999EXIT.s
/etc/lcd.d/10main.m/12ADVANCED_NETWORK.m/999EXIT.s/10exit

%dir /etc/lcd.d/10main.m/40RESET_NETWORK.s
/etc/lcd.d/10main.m/40RESET_NETWORK.s/10reset_net

%dir /etc/lcd.d/10main.m/50RESET_FILTERS.s
/etc/lcd.d/10main.m/50RESET_FILTERS.s/10reset_filters

%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/999EXIT.s
/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/999EXIT.s/10exit
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/10ENGLISH.s
/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/10ENGLISH.s/10select_english
#%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/20JAPANESE.s
#/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/20JAPANESE.s/10select_japanese
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/30FRENCH.s
/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/30FRENCH.s/10select_french
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/40GERMAN.s
/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/40GERMAN.s/10select_german
%dir /etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/50SPANISH.s
/etc/lcd.d/10main.m/60SELECT_LANGUAGE.m/50SPANISH.s/10select_spanish
%dir /etc/lcd.d/10main.m/999EXIT.s

%dir /etc/lcd.d/10main.m/90RESET_PASSWORD.s
/etc/lcd.d/10main.m/90RESET_PASSWORD.s/10reset_password

%dir /etc/lcd.d/10main.m/20REBOOT.s
/etc/lcd.d/10main.m/20REBOOT.s/10reboot
%dir /etc/lcd.d/10main.m/30POWER_DOWN.s
/etc/lcd.d/10main.m/30POWER_DOWN.s/10off

%dir /etc/lcd.d/10main.m/35PANEL.m
%dir /etc/lcd.d/10main.m/35PANEL.m/10LOCK_PANEL.s
/etc/lcd.d/10main.m/35PANEL.m/10LOCK_PANEL.s/10lock
%attr(0700,-,-) /etc/lcd.d/10main.m/35PANEL.m/10LOCK_PANEL.s/string
%dir /etc/lcd.d/10main.m/35PANEL.m/20SET_SEQUENCE.s
/etc/lcd.d/10main.m/35PANEL.m/20SET_SEQUENCE.s/10setseq
%attr(0700,-,-) /etc/lcd.d/10main.m/35PANEL.m/20SET_SEQUENCE.s/string
%attr(0700,-,-) /etc/lcd.d/10main.m/90RESET_PASSWORD.s/string

/etc/lcd.d/10main.m/999EXIT.s/10exit
/etc/lcd.d/button_list
/etc/lcd.d/reset_password
/usr/local/sbin/fppasswd.sh


