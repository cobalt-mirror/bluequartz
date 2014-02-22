Summary: Wrapper program for MHonArc
Name: mh-wrapper
Version: 1.0.1
Release: 3
Copyright: GPL
Group: Silly
Source: mh-wrapper.tar.gz

%description
The secure suid wrapper for MHonArc.

%prep
%setup -n mh-wrapper

%build
make

%install
make install

%files
%attr(04750,mail,daemon) /home/mhonarc/bin/mh-wrapper
/usr/adm/sm.bin/mh-wrapper

%changelog
* Fri Jan 25 2002 Patrick Baltz <patrick.baltz@sun.com>
- pr 13574.  remove group write permissions from mh-wrapper

* Mon May 16 2000 Jonathan Mayer <jmayer@cobalt.com>
- initial spec file
