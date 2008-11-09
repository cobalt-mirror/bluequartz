Summary: Active Monitor support for base-java-am
Name: base-java-am
Version: 1.0
Release: 2
Copyright: 2002 Sun Microsystems, Inc.
Group: Utils
Source: base-java-am.tar.gz
BuildRoot: /tmp/base-java-am

%prep
%setup -n base-java-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%files
/usr/sausalito/swatch/bin/*

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-java-am.  

%changelog
* Mon Jan 28 2002 Patrick Baltz <patrick.baltz@sun.com>
- pr 13680.  For some reason tomcat stopped returning Servlet-Engine, so
  look for the "HTTP/1.1 404 Not" as the response.  If tomcat is actually
  dead, an internal server error will be returned.

* Thu Jan 10 2002 Patrick Baltz <patrick.baltz@sun.com>
- initial rpm for new expect style java test to fix pr 12860
