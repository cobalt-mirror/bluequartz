Name: majordomo
Version: 1.94.4.1
Release: CC13
Summary: majordomo mailing list manager
Copyright: Great Circle Associates
Group: Applications/Mail
Requires: shadow-utils sendmail
Source: majordomo.tar.gz
# BuildRoot: /var/tmp/majordomo
Packager: Sridhar Gopal <gsri@cobaltnet.com>

%changelog
* Mon May 18 2000 Jonathan Mayer <jmayer@cobalt.com>
- applied -carmel patch, makes addr_match do the right thing
  for local members (ie. members w/o "@" in their emails).

* Mon May 15 2000 Jonathan Mayer <jmayer@cobalt.com>
- fixed insecure permissions on wrapper

* Mon May 1 2000 Duncan Laurie <duncan@cobalt.com>
- 1.94.4-C10
- put alaises.majordomo in /etc/mail

* Tue Nov 9 1999 Duncan Laurie <duncan@cobalt.com>
- 1.94.4-1C9
- group admin doesn't exist when this is installed,
  so make it group 'daemon'
- this rpm is a piece of crap

* Sun Jun 27 1999 Sridhar Gopal <gsri@cobaltnet.com>
  - virtual majordomo
  - majordomo user.group == mail.admin
  - virtual sites have a directory readable by the site admin group
  - restrict_post allows only list members

* Thu May 27 1999 Sridhar Gopal <gsri@cobaltnet.com>
  - add regexp '/./' to noadvertise so nobody can do 'lists'
  - set who_access to 'closed' so list members are not disclosed

* Sat Mar 27 1999 Sridhar Gopal <gsri@cobaltnet.com>
  - up the max message length limit in config_parse.pl to 5MB

* Fri Mar 12 1999 Sridhar Gopal <gsri@cobaltnet.com>
  - post-install need not run newaliases as AutoRebuildAliases is set
  - delete reply-to fields in message header

* Sun Feb 28 1999 Sridhar Gopal <gsri@cobaltnet.com>
  - combine cobalt patches
  - use resend in wrapper
  - add resend and reply-to fields in message header
  - post-install copies /etc/sendmail.cf to /etc/sendmail.cf.master

* Fri Dec 4 1998 Sridhar Gopal <gsri@cobaltnet.com>
  - change permissions for LISTDIR from 0775 to 0755
  - eat up diagnostic outputs

* Tue Oct 20 1998 Sridhar Gopal <gsri@cobaltnet.com>
  - Automate installation.

%description 
Majordomo is a program which automates the management of Internet mailing
lists. Commands are sent to Majordomo via electronic mail to handle all
aspects of list maintainance. Once a list is set up, virtually all operations
can be performed remotely, requiring no intervention upon the postmaster of
the list site. 

Majordomo controls a list of addresses for some mail transport system (like
sendmail or smail) to handle.  Majordomo itself performs no mail delivery
(though it has scripts to format and archive messages). 

%prep
rm -rf /usr/local/majordomo
# rm -rf $RPM_BUILD_ROOT

%setup -n majordomo

%build
make wrapper

%pre
U=mail
G=daemon
M=majordomo
HD=/usr/local/majordomo

%install
U=mail
G=daemon
M=majordomo
HD=usr/local/majordomo
AL=etc/mail/aliases.majordomo

mkdir -p $RPM_BUILD_ROOT/etc/mail
mkdir -p $RPM_BUILD_ROOT/usr/man/man{1,8}

mkdir -p /$HD $RPM_BUILD_ROOT/$HD
chown $U.$G /$HD $RPM_BUILD_ROOT/$HD
chmod 751 /$HD $RPM_BUILD_ROOT/$HD

echo "********************* making install ****************"
su $U -c "make install"
echo "*********************** done ************************"
make MAN=$RPM_BUILD_ROOT/usr/man install-man install-wrapper

rm -rf /$HD/lists /$HD/digests /$HD/files /$HD/log

(
  cd /$HD
  tar cf - * |
  (
    cd $RPM_BUILD_ROOT/$HD
    tar xvvf -
  )
)

touch $RPM_BUILD_ROOT/$HD/log
chown $U.$G $RPM_BUILD_ROOT/$HD/log
chmod 660 $RPM_BUILD_ROOT/$HD/log

for dir in lists digests files; do
  mkdir -p $RPM_BUILD_ROOT/$HD/$dir
  chown $U.$G $RPM_BUILD_ROOT/$HD/$dir
  chmod 2750 $RPM_BUILD_ROOT/$HD/$dir
done

install -m644 -o root -g root aliases.majordomo \
	$RPM_BUILD_ROOT/$AL
cat $RPM_BUILD_ROOT/$AL

# fix wrapper
chmod o-rwx /usr/local/majordomo/wrapper

%post
U=mail
G=daemon
M=majordomo
HD=/usr/local/majordomo
CF=/etc/mail/sendmail.cf
AL=/etc/mail/aliases.majordomo

cat <<END > /var/tmp/sed.script
/^O AliasFile=\/etc\/mail\/aliases$/a\\
O AliasFile=$AL
END

if ! grep -q "^O AliasFile=$AL" $CF
then
  sed -f /var/tmp/sed.script $CF > $CF.new
  mv -f $CF.new $CF
fi
rm -f /var/tmp/sed.script

/usr/bin/newaliases &> /dev/null

%clean
U=mail
G=daemon
M=majordomo
HD=/usr/local/majordomo
rm -rf $RPM_BUILD_ROOT
rm -rf $HD

%postun
U=mail
G=daemon
M=majordomo
HD=/usr/local/majordomo
CF=/etc/mail/sendmail.cf
AL=/etc/mail/aliases.majordomo

sed -e '/^O AliasFile=\/etc\/mail\/aliases.majordomo$/d' $CF > $CF.old
mv -f $CF.old $CF
rm -f $AL $AL.db
rm -rf $HD

%files
%dir /usr/local/majordomo/lists
%dir /usr/local/majordomo/digests
%dir /usr/local/majordomo/files
/usr/local/majordomo/Tools/archive.pl
/usr/local/majordomo/Tools/archive_mh.pl
/usr/local/majordomo/Tools/digest.send
/usr/local/majordomo/Tools/logsummary.pl
/usr/local/majordomo/Tools/makeindex.pl
/usr/local/majordomo/Tools/new-list
/usr/local/majordomo/Tools/sequencer
/usr/local/majordomo/archive2.pl
/usr/local/majordomo/bin/approve
/usr/local/majordomo/bin/bounce
/usr/local/majordomo/bin/medit
/usr/local/majordomo/bounce-remind
/usr/local/majordomo/config-test
/usr/local/majordomo/config_parse.pl
/usr/local/majordomo/digest
/usr/local/majordomo/log
/usr/local/majordomo/majordomo
/usr/local/majordomo/majordomo.cf
/usr/local/majordomo/majordomo.pl
/usr/local/majordomo/majordomo_version.pl
/usr/local/majordomo/request-answer
/usr/local/majordomo/resend
/usr/local/majordomo/sample.cf
/usr/local/majordomo/shlock.pl
/usr/local/majordomo/wrapper
%config /etc/mail/aliases.majordomo
%doc /usr/man/man1/approve.1
%doc /usr/man/man1/digest.1
%doc /usr/man/man1/bounce.1
%doc /usr/man/man1/bounce-remind.1
%doc /usr/man/man1/resend.1
%doc /usr/man/man8/majordomo.8
