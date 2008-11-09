Summary: Wrapper program for MHonArc
Name: mh-wrapper
Version: 1.0.2
Release: 1
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
%attr (04750,mail,daemon) /home/mhonarc/bin/mh-wrapper
/usr/adm/sm.bin/mh-wrapper

%changelog
* Wed Aug 21 2002 Stephen Grell <stephen.grell@sun.com>
- PR 15106 Remove group write permissions from mh-wrapper.

* Mon May 16 2000 Jonathan Mayer <jmayer@cobalt.com>
- initial spec file
