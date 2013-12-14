Summary: Cobalt development tools
Name: sausalito-devel-tools
Version: 0.5.1
Release: 0BQ21%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: %{name}.tar.gz
Prefix: /usr/sausalito
BuildRoot: /var/tmp/devel-root
Provides: perl(BTO)
BuildRequires: glib-devel

%description
sausalito-devel-tools the basic Cobalt development environment.

%prep
%setup -n %{name}

%build
make PREFIX=$RPM_BUILD_ROOT

%install
rm -rf $RPM_BUILD_ROOT
make install PREFIX=$RPM_BUILD_ROOT

%postun
if [ $1 -eq 0 ]; then
  for i in /usr/lib/rpm/rpmrc /usr/lib/rpm/redhat/rpmrc; do
    if test -f "$i" && egrep -q '^macrofiles:.*%{_sysconfdir}/rpm/macros\.blueonyx' "$i"; then
      perl -pi -e \
        's,^(macrofiles:.*):%{_sysconfdir}/rpm/macros\.blueonyx,$1,' "$i"
    fi
  done
fi

%triggerin -- rpm, redhat-rpm-config, /usr/lib/rpm/rpmrc, /usr/lib/rpm/redhat/rpmrc
for i in /usr/lib/rpm/rpmrc /usr/lib/rpm/redhat/rpmrc; do
  if test -f "$i" && ! egrep -q '^macrofiles:.*%{_sysconfdir}/rpm/macros\.blueonyx' "$i"; then
    perl -pi -e \
      's,^(macrofiles:.*?)(:~/.*)?$,$1:%{_sysconfdir}/rpm/macros\.blueonyx$2,' "$i"
  fi
done

%files
%defattr(-,root,root)
/usr/sausalito/devel/*
/usr/sausalito/lib/*
/usr/sausalito/include/*
/usr/sausalito/bin/*
/usr/sausalito/perl/*
/etc/rpm/macros.blueonyx

%changelog
* Sat Mar 03 2012 Greg Kuhnerg 0.5.1-0BQ21
- Updated module.mk to fix a few problems for building PKG files
- updated makePkg to copy scripts directory

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ20
- Updated for name change to BlueOnyx.

* Wed Nov 12 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ19
- Removed changelog entries from templates/spec.tmpl 

* Sun Jun 01 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ17
- rules/defines.mk updated for 51006R

* Sun Jan 27 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ16
- add sign to the package.

* Sun Apr 29 2007 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ15
- support subversion repository again.

* Thu Oct 13 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ14
- support subversion repository.

* Thu Sep 07 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ13
- modify mod_rpmize to write TRIGGERIN and TRIGGERUN section at once.

* Thu Aug 17 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ12
- add REQUIRES_UI for requires in ui package.

* Tue Jun 27 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ11
- modify mod_romize to fix the REQUIRES_GLUE issue which isn't set.

* Tue Jun 27 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ10
- add REQUIRES_GLUE for requires in glue package.

* Tue Feb 14 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ9
- add TRIGGERIN and TRIGGERUN sections.

* Fri Dec 16 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ8
- support CentOS4 alpha as 5105R.

* Fri Nov 25 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ7
- modified mod_rpmize to remove version and release for requires.

* Mon Nov 07 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ6
- remove vendor macro from macros.bluequartz.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ5
- use php-config to get extension directory.
- use PACKAGE_DIR instead of REDHAT_DIR like as /usr/src/redhat.

* Thu Oct 20 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ4
- added vendor tag in macros.bluequartz

* Tue Oct 18 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ3
- modified macros.bluequartz to add dist macros.

* Tue Oct 18 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ2
- modified mod_rpmize to add %{?dist} to require and provides release number.

* Mon Oct 17 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ1
- changed version number to 0.5.1 as new build system.

* Mon Oct 17 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ15
- modified spec.tmpl to add %{?dist} macro to the release number.

* Mon Oct 17 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ14
- remove SERIAL and Serial tag.
- add macros.bleuqartz for rpm.
- use License tag instead of Copyright.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpha.or.jp. 0.5.0-117BQ13
- modified Group tag in rpmdefs.tmpl.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpha.or.jp. 0.5.0-117BQ12
- add SERIAL with provides and requires.

* Fri Aug 12 2005 Hisao SHIBUYA <shibuya@alpha.or.jp. 0.5.0-117BQ11
- add SERIAL environment for Makefile.

* Tue Aug 09 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ10
- clean up module.mk.

* Tue Aug 09 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ9
- modified module.mk to wait 1 second before building src_rpms.

* Tue Aug 09 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ8
- modified mod_rpmize to support CHANGELOG section.

* Tue Aug 09 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ6
- modified mod_rpmize to add version and release for requires.

* Tue Aug 09 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ5
- add code to handle multi distoribution into spec.tmpl.

* Sat Jun 16 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ4
- support x86_64 environment.

* Wed Aug 17 2004 Takashi Matsuo <tmatsuo@10art-ni.co.jp> 0.5.0-117BQ3
- Now non-root-user can 'make rpm'.

* Fri Apr 04 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ2
- fix REQUIRES option for Makefile

* Tue Dec 23 2003 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-117BQ1
- build for Blue Quartz.

* Wed Jan 24 2001 Patrick Baltz <patrick.baltz@sun.com>
- install make_release and move a few functions into Devel.pm so other scripts can use them

* Tue May 02 2000 Adrian Sun <asun@cobalt.com>
- moved template files here

* Mon Apr 24 2000 Adrian Sun <asun@cobalt.com>
- added new build scripts

* Tue Mar 14 2000 Adrian Sun <asun@cobalt.com>
- renamed, and now includes other stuff like libdebug and cpan2rpm as well.

* Thu Mar 09 2000 Adrian Sun <asun@cobalt.com>
- initial devel version
