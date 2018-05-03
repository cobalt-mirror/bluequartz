Name:		net2ftp
Version:	1.0.0
Release:	1
Packager:	Michael Stauber <mstauber@blueonyx.it>
Vendor:		BLUEONYX.IT
URL:		http://www.blueonyx.it
License:	GNU GPL
Group:		System
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
BuildArch:      noarch
Distribution: 	BlueOnyx 520XR
Source:		%{name}.tar.gz

AutoReq: 	no
Summary:	net2ftp integration for BlueOnyx

%description
net2ftp integration for BlueOnyx

%prep
%setup -q -n %{name}

%build   

%install
%{__rm} -rf %{buildroot}
# set up path structure
%{__install} -d -m 0755 %{buildroot}/usr/sausalito/ui/web/
cd net2ftp
mv ftpclient %{buildroot}/usr/sausalito/ui/web/

%pre

%post
chown admserv:admserv /usr/sausalito/ui/web/ftpclient/temp
chmod 777 /usr/sausalito/ui/web/ftpclient/temp

%preun

%clean
%{__rm} -rf %{buildroot}

%files
%defattr(0644,root,root,0755)
%dir /usr/sausalito/ui/web/ftpclient
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/plugins/*
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/temp/*
%attr(0644,root,root) /usr/sausalito/ui/web/ftpclient/temp/.htaccess
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/includes/*
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/languages/*
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/modules/*
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/skins/*
%attr(0644,root,root) /usr/sausalito/ui/web/ftpclient/LICENSE.txt
%attr(0644,root,root) /usr/sausalito/ui/web/ftpclient/favicon.png
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/index.xml.php
%attr(0644,root,root) /usr/sausalito/ui/web/ftpclient/robots.txt
%attr(0644,root,root) /usr/sausalito/ui/web/ftpclient/htaccess.txt
%attr(0644,root,root) /usr/sausalito/ui/web/ftpclient/version.js
%attr(0644,root,root) /usr/sausalito/ui/web/ftpclient/favicon.ico
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/settings_screens.inc.php
%attr(0644,root,root) /usr/sausalito/ui/web/ftpclient/help.html
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/index.php
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/settings_authorizations.inc.php
%attr(0755,root,root) /usr/sausalito/ui/web/ftpclient/settings.inc.php
%attr(0644,root,root) /usr/sausalito/ui/web/ftpclient/package_list.txt

%changelog

* Wed May 02 2018 Michael Stauber <mstauber@solarspeed.net> 1.0.0-1
- Initial build


