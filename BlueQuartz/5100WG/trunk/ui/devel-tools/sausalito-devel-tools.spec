Summary: Cobalt development tools
Name: sausalito-devel-tools
Version: 0.5.0
Release: 117OQ2
Copyright: Cobalt
Group: Sausalito/Development/Tools
Source: %{name}.tar.gz
Prefix: /usr/sausalito
BuildRoot: /var/tmp/devel-root
Provides: perl(BTO)

%description
sausalito-devel-tools the basic Cobalt development environment.

%prep
%setup -n %{name}

%build
make PREFIX=$RPM_BUILD_ROOT

%install
rm -rf $RPM_BUILD_ROOT
make install PREFIX=$RPM_BUILD_ROOT

%files
%defattr(-,root,root)
/usr/sausalito/devel/*
/usr/sausalito/lib/*
/usr/sausalito/include/*
/usr/sausalito/bin/*
/usr/sausalito/perl/*

%changelog
* Thu Jul 24 2003 Hisao SHIBUYA <shibuya@alpha.or.jp>
- (0.5.0-117OQ2)
- fix option XLOCALEPAT=zh_CN zh_TW to avoid msgfmt problem.
- fix mod_rpmize with blank ccewrap dir.
- fix module.mk with blank ccewrap dir.

* Tue Jul 22 2003 Hisao SHIBUYA <shibuya@alpha.or.jp>
- (0.5.0-117OQ1)
- build for RedHat 9.
- change CCEPHPLIBDIR for RedHat 9.
- use en and ja locale only.
- use rpmbuild for building rpm package.
- fix mod_rpmize.

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
