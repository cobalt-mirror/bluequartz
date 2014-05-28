Summary: Cobalt UI Library
Name: sausalito-palette
Version: 0.9.9
Release: 0BX01%{?dist}
Vendor: Project BlueOnyx
License: Sun modified BSD
Group: System Environment/BlueOnyx
Source: sausalito-palette.tar.gz
Prefix: /usr/sausalito
BuildRoot: /var/tmp/sausalito-palette-root
BuildArchitectures: noarch

%description
sausalito-palette has all of the Cobalt UI functions.

%prep
%setup -n sausalito-palette

%build
make CCETOPDIR=/usr/sausalito

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/usr/sausalito/ui
make install PREFIX=$RPM_BUILD_ROOT  CCETOPDIR=/usr/sausalito

%post
#Integration code so Monterey can use uifc
if [ -d /usr/admserv/html ]; then
    if [ ! -e /usr/admserv/html/libImage ]; then 
	ln -n -s /usr/sausalito/ui/web/libImage /usr/admserv/html/libImage;
    fi
    if [ ! -e /usr/admserv/html/libJs ]; then 
	ln -n -s /usr/sausalito/ui/web/libJs /usr/admserv/html/libJs;
    fi
    if [ ! -e /usr/admserv/html/nav ]; then 
	ln -n -s /usr/sausalito/ui/web/nav /usr/admserv/html/nav;
    fi
    if [ ! -e /usr/admserv/html/base ]; then 
	ln -n -s /usr/sausalito/ui/web/base /usr/admserv/html/base;
    fi
    if [ ! -e /usr/admserv/html/uifc ]; then 
	ln -n -s /usr/sausalito/ui/web/uifc /usr/admserv/html/uifc;
    fi
fi

%files
%defattr(-,root,root)
/usr/sausalito/ui/menu/palette
/usr/sausalito/ui/conf
/usr/sausalito/ui/libPhp
/usr/sausalito/ui/web
/usr/sausalito/sbin/writeFile.pl
/usr/share/locale/*/LC_MESSAGES/*
/usr/share/locale/*/*.prop
/etc/ccewrap.d/*

%changelog

* Wed May 28 2014 Michael Stauber <mstauber@solarspeed.net> 0.9.9-0BX02
- Modified libPhp/ServerScriptHelper.php with check if CCEd is responsive.
- If not, it now has the capability to restart it.

* Thu Apr 24 2014 Michael Stauber <mstauber@solarspeed.net> 0.9.9-0BX01
- New major version for 520XR.
- Modified conf/ui.cfg for Chorizo based 520XR.

* Fri Apr 04 2014 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX35
- Added libPhp/BXBrowserLocale.php to properly detect the browser locale.

* Wed Apr 02 2014 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX34
- Last change (0.5.2-0BX33) works fine on all platforms but 5106R. The old
  PHP-5.1.6 shows its ugly face. Added platform specific alterations that
  kick in when the platform is 5106R and the language is Japanese.
- Modified libPhp/CobaltUI.php - don't we just love that sucker.
- Modified libPhp/uifc/Button.php
- Modified libPhp/uifc/Label.php
- Modified libPhp/uifc/BXLocale.php

* Wed Apr 02 2014 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX33
- Added improved French locale as provided by Meaulnes Legler.
- In 0.5.2-0BX26 we switched to pure UTF-8 locales, but they didn't work
  right. So the dirty work around with the 'windows-1252' charset was
  introduced. We now say good bye to that and switch to pure UTF-8
  for real.
- Charset change in web/nav/flow.php
- Charset change in web/nav/cList.php
- Charset change in web/nav/single.php
- Charset change in libPhp/uifc/Page.php

* Thu Feb 27 2014 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX32
- Small locale fix in German locales.

* Fri Feb 21 2014 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX31
- Modified libPhp/uifc/HtmlComponentFactory.php to change the === comparator
  on getInteger() and getNumber() to something else that seems to work better.

* Mon Feb 10 2014 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX30
- Added libPhp/BXEncoding.php
- Modified libPhp/uifc/TextBlock.php as it was getting its value via round about.

* Sat Dec 14 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX29
- Merged in locales for the Netherlands ('nl_NL').

* Mon Dec 09 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX28
- Small fix to web/nav/flow.php

* Sun Dec 08 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX27
- Various charset fixes. They help some, but under Spanish, Portuguese
  and French the lefthand side menu is still buggered. Toplevel menu
  entries with accents or special characters are garbled. That's beyond
  my ability to fix at this time. It works in the new GUI, so to hell with it.

* Sat Dec 07 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX26
- Preparational build for 5207R/5208R. Doesn't include new GUI yet.
- Merged in new locales from 5207R ("es_ES", "fr_FR", "it_IT", "pt_PT").
- Dropped all two character locales.
- Converted "ja_JP" from EUC-JP to UTF-8.
- When implemneting the four new locales ("es_ES", "fr_FR", "it_IT", "pt_PT") the code
  for rendering the pages blew up right in my face. i18n's function on detecting the charset
  of locales somehow believes Spanish, French and so on needs EUC-JP. Oh, and our perfectly
  fine UTF-8 locales only display correctly, if we use the 'windows-1252' charset for the
  western languages and EUC-JP for Japanese. How sick is that? Anyway, I hard coded the
  charsets into /nav/cList.php and uifc/Page.php for now.

* Tue Oct 01 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX25
- Weird non-reproduceable conflict with Classes Locale and Collator conflicting with
  native PHP functions. Solved by renaming the Classes.
- Removed libPhp/uifc/Locale.php
- Added libPhp/uifc/BXLocale.php
- Removed libPhp/Collator.php
- Added libPhp/BXCollator.php
- Modified libPhp/uifc/ScrollList.php
- Modified libPhp/uifc/HtmlComponentFactory.php

* Thu May 09 2013 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX24
- Modified libPhp/uifc/FormFieldBuilder.php to change a hard coded 'UTF-8' transition
  into something that detects and uses the proper charset.

* Wed Aug 15 2012 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX23
- Extended libPhp/uifc/SetSelector.php with the ability to generate longer selectors.

* Mon Jul 30 2012 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX22
- Updated libPhp/uifc/FormFieldBuilder.php again to remove the hidden field output of 
  the new UIFC function getHtmlField(), as we really do not need it. It just clutters up
  the HTML output of generated pages and doubles load time.

* Wed Jul 25 2012 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX21
- Added libPhp/uifc/HtmlField.php to add UIFC function getHtmlField(), which works similar
  to getTextField(), but allows HTML code.
- Modified libPhp/uifc/FormFieldBuilder.php and libPhp/uifc/HtmlComponentFactory.php to
  allow usage of getHtmlField().

- Fix to libPhp/uifc/FormFieldBuilder.php to get it from timing out on conversion issues.

* Tue Mar 20 2012 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX20
- Fix to libPhp/uifc/FormFieldBuilder.php to get it from timing out on conversion issues.

* Tue Mar 20 2012 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX19
- Converted locale encoding from UTF-8 to ISO-8859-1.

* Wed Sep 07 2011 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX18
- Updated libPhp/uifc/TimeStamp.php used to set the TimeZone to UTC, which is a bad idea.
  We want our date and time form fields to show the server time instead. For this we now
  depend on 'date.timezone' in /etc/admserv/php.ini being set to the servers timezone.

* Thu Sep 01 2011 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX17
- Modified web/nav/cList.php to make it break out of a frameset if it has been loaded into one.

* Mon Aug 29 2011 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX16
- Modified libPhp/ServerScriptHelper.php libPhp/uifc/Page.php libPhp/utils/page.php
  to remove trailing head block from all pages to make pages more compliant with 
  proper HTML standards. The trailing head block with a pragma to not cache GUI
  pages was once added as a work around for a really ancient version of Internet
  Explorer, which since long has been retired. So we do not need this anymore.

* Tue Aug 16 2011 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX15
- Modified libPhp/uifc/FormFieldBuilder.php as there may only be three parameters instead of four to htmlspecialchars.

* Fri Aug 12 2011 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX14
- Modified web/nav/cList.php web/nav/single.php web/nav/flow.php web/loginHandler.php and web/logoutHandler.php
- Removed trailing and redundant <head></head> block from all pages.
- Removed Netscape onresize reload from all pages that had it, as it's interfering with newer mobile devices.

* Tue Jun 08 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX13
- libPhp/uifc/FormFieldBuilder.php: Function makeTextAreaField had no font set.

* Sun Jun 06 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX12
- Fixed missing font size in libPhp/uifc/CompositeFormField.php

* Sun Jun 06 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX11
- Fixed libPhp/uifc/MultiChoice.php again.

* Sun Jun 06 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX10
- Multitabbed pages loose the contends of MultiChoice unless you click through all tabs first
- htmlspecialchars() no longer works on Arrays in PHP5
- Activated experimental subroutine in libPhp/uifc/FormFieldBuilder.php to compensate that.
- I think the Id field also needed that modification in libPhp/uifc/MultiChoice.php. Added it.

* Sun Jun 06 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX09
- Re-added missing TD style for TextLists to libPhp/uifc/MultiChoice.php

* Sun Jun 06 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX08
- Replaced ours libPhp/uifc/MultiChoice.php with the one from BQ, which is longer.
- Extended libPhp/uifc/FormFieldBuilder.php with an experimental subroutine
- deals with oddities of htmlspecialchars() in PHP-5.3. Currently disabled.

* Sat Jun 05 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX07
- Added Hisao's fix for the MultiSelect function

* Sat Jun 05 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX06
- Copied 'en' locales to 'en_US'

* Fri Jun 04 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX05
- libPhp/ServerScriptHelper.php: date_default_timezone_set added as per Rickard's suggestion in [Devel:00444]

* Fri Jun 04 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX04
- libPhp/uifc/TimeStamp.php: Added date_default_timezone_set hardwired to UTC

* Fri Jun 04 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX03
- libPhp/CobaltUI.php: Assigning the return value of new by reference is deprecated

* Fri Jun 04 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX02
- web/uifc/MultiFileUploadHandler.php: ereg() replaced by preg_match() for PHP-5.3
- web/uifc/MultiFileUpload.php: ereg() replaced by preg_match() for PHP-5.3
- libPhp/uifc/PagedBlock.php: ereg() replaced by preg_match() for PHP-5.3

* Fri Jun 04 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.2-0BX01
- libPhp/ServerScriptHelper.php: ereg() replaced by preg_match() for PHP-5.3
- libPhp/uifc/EmailAddressList.php: ereg() replaced by preg_match() for PHP-5.3
- libPhp/uifc/IntRange.php: ereg() replaced by preg_match() for PHP-5.3
- libPhp/utils/file.php: ereg() replaced by preg_match() for PHP-5.3
- libPhp/Capabilities.php: Line 47 - Assigning the return value of new by reference is deprecated

* Wed Jun 02 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BX24
- Version number change for more consistency.

* Sun May 30 2010 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ23
- Fixed font size in libPhp/uifc/SimpleText.php

* Mon Nov 09 2009 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ22
- Updated libPhp/uifc/VerticalCompositeFormField.php to remove blank lines at end of file. They cause display errors.

* Mon Nov 09 2009 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ21
- Updated libPhp/uifc/VerticalCompositeFormField.php to add 12px font size to style.
- This fixes a display problem that - so far - only exists on Aventurin{e}.

* Sun Nov 08 2009 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ20
- Added missing TD style for TextLists in libPhp/uifc/MultiChoice.php 
- This fixes a display problem that - so far - only exists on Aventurin{e}.

* Fri Jul 17 2009 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ19
- Modified libPhp/uifc/ScrollList.php to fix CSS issue with formField-1.
- This finally applies the correct font size and styles to text in ScrlollLists again.

* Wed Dec 03 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ18
- Rebuilt for BlueOnyx.

* Sun Nov 23 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ17
- Found another piece of faulty code that cause missing pagedBlock elements:
- Updated libPhp/uifc/PagedBlock.php to change reference to a straight pointer.

* Sun Nov 23 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ16
- Brian found the faulty code that cause missing pagedBlock elements:
- Updated libPhp/uifc/PagedBlock.php to change reference to a straight pointer.

* Wed Nov 19 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ15
- Changed default skin to BlueOnyx

* Wed Nov 19 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ14
- Fixed PHP5 related issue in web/status.php

* Wed Sep 10 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ13
- Ajax and JS based password strength checker implemented directly through uifc calls.
- Newly added files for that:
- web/libJs/ajax_lib.js
- web/uifc/check_password.php
- Modified the following pages for that:
- libPhp/uifc/FormFieldBuilder.php
- libPhp/uifc/Password.php
- libPhp/uifc/Page.php

* Tue Sep 09 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ12
- Tab positions in navigation menu (tab.js) fixed for Chrome and Safari. 
- Contributed by Jeremy Knope from rainstormconsulting.com

* Tue Sep 09 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ11
- fixes #10
- fix for Safari 3.0 missing menu for wizard by Anders, BlackSun Inc.

* Sun Jun 08 2008 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ10
- fixes #10
- fix for Safari 3.0 missing menus by Anders, BlackSun Inc.

* Mon May 26 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ9
- Fix in de_DE/palette.po

* Mon Jan 28 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ8
- Fix in de_DE/palette.po

* Sun Jan 27 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ7
- German locales added.

* Tue Jan 22 2008 Michael Stauber <mstauber@solarspeed.net> 0.5.1-0BQ6
- Danish locales added. Thanks to Jes Klittum!

* Mon Jun 25 2007 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ5
- Fixed duplicate include issue.

* Sat May 05 2007 Hisao SHIBUYA <shibuya@bluequartz.org> 0.5.1-0BQ4
- Fixed duplicate include issue.
- Fixed PagedBlock.php, fucntion toHtml. Don't call function of non object.

* Fri Aug 04 2006 Brian Smith <brian@nuonce.net> 0.5.1-0BQ3
- Fixed ServerScriptHelper.php, function getFile.  Wouldn't load past 4096 bytes.

* Thu Jul 20 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ2
- add InetAddress class.

* Thu Mar 09 2006 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.1-0BQ1
- add netmask class.
- merge Merlot style.

* Sun Dec 18 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ10
- modified JavaScript to fix the browser issue with Safari by Anders, BlackSun, Inc.

* Tue Nov 29 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ9
- rebuild with devel-tools 0.5.1-0BQ7.

* Mon Oct 31 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ8
- add dist macro for release.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ7
- use vendor macro for Vendor tag.

* Fri Oct 21 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ6
- use PACKAGE_DIR instead of /usr/src/redhat.

* Tue Oct 18 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ5
- rebuild with devel-tools 0.5.1

* Mon Aug 15 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ4
- clean up spec file.

* Wed Aug 10 2005 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ3
- change user length to 31 instead of 12.

* Fri Mar 05 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ2
- fix Japanese charset in palette.prop

* Tue Jan 08 2004 Hisao SHIBUYA <shibuya@alpha.or.jp> 0.5.0-93BQ1
- build for Blue Quartz

* Wed Nov 01 2000 Philip Martin <pmartin@cobalt.com>
- include the .prop files in the rpm

* Tue May 02 2000 Adrian Sun <asun@cobalt.com>
- make sure the correct permissions get used

* Wed Apr 26 2000 Adrian Sun <asun@cobalt.com>
- renamed

* Thu Mar 09 2000 Adrian Sun <asun@cobalt.com>
- initial palette spec file.
