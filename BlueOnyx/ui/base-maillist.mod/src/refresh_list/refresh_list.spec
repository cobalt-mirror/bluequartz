Summary: Program that tells CCE to re-scan mailing list membership.
Name: refresh_list
Version: 1.0.1
Release: 2
Copyright: Cobalt Networks
Group: Silly
Source: refresh_list.tar.gz

%description
A suid program that tells CCE that it's now time to update
mailing list memberhip in it's database.

%prep
%setup -n refresh_list

%build
make

%install
make install

%files
/usr/sausalito/bin/refresh_list

%changelog
* Sun Sep 17 2000 Jonathan Mayer <jmayer@cobalt.com>
- initial spec file

