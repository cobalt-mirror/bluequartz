Summary: Mail-to-HTML Converter
Name: MHonArc
Version: 2.4.6.1
Release: 1
Copyright: GPL
Group: Silly
Source: MHonArc.tar.gz

%description
MHonArc is a Perl mail-to-HTML converter. MHonArc provides HTML mail 
archiving with index, mail thread linking, etc; plus other capabilities
including support for MIME and powerful user customization features. 

%prep
%setup -n MHonArc

%build
perl Makefile.PL PREFIX=/home/mhonarc
make

%install
\rm -rf /home/mhonarc
make install
chown -R mail /home/mhonarc

%files
/home/mhonarc

%changelog
* Mon May 15 2000 Jonathan Mayer <jmayer@cobalt.com>
- initial spec file
