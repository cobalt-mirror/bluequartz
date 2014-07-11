Summary: Cobalt development tools
Name: sausalito-devel-tools
Version: 0.6.0
Release: 0BX01%{?dist}
Vendor: %{vendor}
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: %{name}.tar.gz
Prefix: /usr/sausalito
BuildRoot: /var/tmp/devel-root
Provides: perl(BTO)
BuildRequires: glib-devel
Requires: cpp gcc glib-ghash imake subversion rpm-build autoconf automake re2c glib-devel file-devel popt-devel rpm-devel libstdc++-devel zlib-devel libgcj-devel gcc-java gcc-c++

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

* Fri Jul 11 2014 Michael Stauber <mstauber@solarspeed.net> 0.6.0-0BX01
- Updated to the new BlueOnyx RPM format for the Chorizo-GUI of 520XR.
- Added new defines to rules/defines.mk 
- Modified rules/module.mk to handle the new directory structure. 
- Modified scripts/mod_rpmize to handle the new directory structure.
- Axed a lot of garbage from glue/etc/rpm/macros.blueonyx
- Additionally a BlueOnyx modules (toplevel) Makefile can now have
- four settings for BUILDUI: yes, no, old and new. Selecting yes will
  build with old and new GUI or whichever of the two is present. 
  Selecting old will build with the old GUI only. Naturally selecting
  new will only build with the new GUI instead.

* Sat Dec 15 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.3-0BX02
- Merged in locales support for the Netherlands ('nl_NL').

* Mon Dec 09 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.3-0BX01
- Another version number bump as there was a bit of dissagreement if it was 0.5.2 or 0.5.1.
- Added a pre section to spec template. Dunno why *that* was missing.

* Fri Dec 06 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BX10
- I always hated it that the capstone did not require all the bloody locales of a module and that ANY 
  locale would satisfy the locale dependency. Well, this is fixed: On build time all existing locales
  are added as requirements to capstone and each locale only satisfies only the dependency for itself.

* Mon Aug 06 2012 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BX09
- Added scripts/packsort.pl, a parser for packing_list. It processes a packing_list and dumps a version
  with semi-correct RPM sort order to STDOUT
- Modified scripts/makePkg to create a packing_list with semi-correct RPM sort order, provided the file
  /usr/sausalito/devel/.pkgsort exists (may be empty).

* Tue Mar 20 2012 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BX08
- Updated dependencies.

* Sat Mar 03 2012 Greg Kuhnerg 0.5.1-0BX07
- Updated module.mk to fix a few problems for building PKG files
- Updated makePkg to copy scripts directory

* Wed Aug 24 2011 Michael Stauber <mstauber@solarspeed.net> 0.5.2-1BXO6
- Updated scripts/mendocino_package to remove path from find command as it is different between EL5 and EL6

* Sun Aug 14 2011 Michael Stauber <mstauber@solarspeed.net> 0.5.2-1BXO5
- Updated Requires to make it easier to get a build system set up.

* Wed Jun 02 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-1BXO4
- Modified rules/defines.mk: Added 'en' to excluded locales, as we now use 'en_US' as default.

* Wed Jun 02 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-1BXO3
- Updated rpm-macros

* Wed May 19 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-1BO2
- Added 5108R version number for 64-bit build
- Bumped version number
- Added strings for CentOS6 to rpm-macros

* Tue Mar 30 2010 Rickard Osser <rickard.osser@bluapp.com> 0.5.2-1BO1
- Fixed for CentOS6 and added small quirks for easier build.

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
