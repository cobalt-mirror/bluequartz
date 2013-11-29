Summary: Active Monitor support for base-java-am
Name: base-java-am
Version: 3.0.0
Release: 1
#Copyright: 2002 Sun Microsystems, Inc.
License: Sun Microsystems modified BSD
Group: Utils
Source: base-java-am.tar.gz
BuildRoot: /tmp/base-java-am

%prep
%setup -n base-java-am

%build
make all

%install
make PREFIX=$RPM_BUILD_ROOT install

%pre

%files
/usr/sausalito/swatch/bin/am_java.exp

%description
This package contains binaries and scripts used by the Active Monitor 
subsystem for base-java-am.  

%changelog

* Sun Jun 26 2011 Michael Stauber <mstauber@solarspeed.net>
- Version number bump for tomcat6 and 5107R.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net>
- Rebuilt for BlueOnyx.

* Wed Nov 26 2008 Michael Stauber <mstauber@solarspeed.net>
- Commented out Copyright tag as it's no longer supported.
- Added new mandatory License tag.
- Bumped version number to three digits.
- Moved tomcat5 logrotate to base-java-capstone to avoid RPM conflicts

* Tue Nov 25 2008 Brian N. Smith <brian.smith@nuonce.net>
- Modified logrotate file, per Michael's request

* Sat Nov 17 2007 Brian N. Smith <brian.smith@nuonce.net>
- Updated to work with the new Tomcat, as the previous one ran differently.

* Mon Jan 28 2002 Patrick Baltz <patrick.baltz@sun.com>
- pr 13680.  For some reason tomcat stopped returning Servlet-Engine, so
  look for the "HTTP/1.1 404 Not" as the response.  If tomcat is actually
  dead, an internal server error will be returned.

* Thu Jan 10 2002 Patrick Baltz <patrick.baltz@sun.com>
- initial rpm for new expect style java test to fix pr 12860
