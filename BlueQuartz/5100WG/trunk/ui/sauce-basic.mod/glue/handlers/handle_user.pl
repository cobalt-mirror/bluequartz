#!/usr/bin/perl -w
# $Id: handle_user.pl 3 2003-07-17 15:19:15Z will $
#
# unified create/modify script for users.
#
# author: Jonathan Mayer <jmayer@cobalt.com>

use strict;
use lib qw( /usr/sausalito/perl );
use Sauce::Util;
use Sauce::Config;
use CCE;
use Jcode;
use FileHandle;
use I18n;

my $DEBUG = 0;

my %illegal_usernames = map { $_ => 1 } qw /
  pop root bin daemon adm lp sync shutdown halt mail news uucp
  operator games gopher ftp httpd nobody 
  postgres mysql qmail share-guest
  majordomo anonymous guest Root Admin ROOT ADMIN squid
/;

# connect:
my $cce = new CCE;
$cce->connectfd();

my $i18n = new I18n;

# retrieve info
my $oid = $cce->event_oid();
my $old = $cce->event_old(); # old values
my $new = $cce->event_new(); # recently changed values only
my $obj = $cce->event_object(); # composite "new" object.

my ($sysoid) = $cce->find("System");
my ($sysobj);
{
  my $ok;
  ($ok, $sysobj) = $cce->get($sysoid);
}

#############################################################################
# step one: validate the data
#############################################################################

my $errors = 0;

if (defined($new->{name})) {
  if ($illegal_usernames{$new->{name}}) {
    $cce->baddata($oid, 'name', '[[base-user.userNameAlreadyTaken]]');
    $errors++;
  }
  my @others = $cce->find("User", { 'name' => $new->{name} } );
  if ($#others > 0) {
    $cce->baddata($oid, 'name', '[[base-user.userNameAlreadyTaken]]');
    $errors++;
  }
  #@others = $cce->find("EmailAlias", { 'name' => $new->{name} } );
  #if ($#others > -1) {
  #	$cce->baddata($oid, 'name', '[[base-user.aliasAlreadyTaken]]');
  #	$errors++;
  #}
}

if(defined($new->{sortName})){
  my $re=$i18n->getProperty("sortNameRegex","base-user");
  if($new->{sortName}!~/$re/ && $re ne "ERROR"){
    $cce->baddata($oid,'sortName','[[base-user.sortNameField_rule]]');
    $errors++;
  }
}

if (defined($new->{quota})) {
  if ($new->{quota} > 10240000) {
    $cce->baddata($oid, 'quota', '[[base-user.excessivelyLargeQuota]]');
    $errors++;
  }
}

if ($errors) {
  $cce->bye('FAIL');
  exit(1);
}

############################################################################
# reserve alias for the user
############################################################################
#if ($new->{name}) {
#	my ($ok) = $cce->create("EmailAlias", { 
#		'name' => $new->{name} ,
#		'action' => '*RESERVED*' ,
#	});
#	if (!$ok) {
#		$cce->warn("[[base-user.userNameAlreadyTaken]]");
#		$cce->bye('FAIL');
#		exit(1);
#	}
#}

if ($new->{name}) { 
  my $ok = Sauce::Util::editfile('/etc/mail/aliases',
    \&Sauce::Util::replace_unique_entries,
    $oid, { $new->{name} => $new->{name} });
  if (!$ok || ($ok eq 'FAIL')) {
    $cce->warn("[[base-user.userNameAlreadyTaken]]");
    $cce->bye("FAIL");
    exit(1);
  }
}

#if ($cce->event_is_destroy() 
#        || (defined($old->{name}) && defined($new->{name}) &&
#		($old->{name} ne $new->{name}) ) ) {
#	my ($oid) = $cce->find("EmailAlias", { 'name' => $old->{name} });
#	$cce->destroy($oid);
#}

#############################################################################
# create or modify the user
#############################################################################

# paraphrase: Cobalt::User::user_add

# A few configurables...
my $WWWgid = Sauce::Config::WWW_group_id;
my $homedir_base = Sauce::Config::homedir_base;
my $useradd = Sauce::Config::bin_useradd;
my $usermod = Sauce::Config::bin_usermod;
my $htpasswd = Sauce::Config::bin_htpasswd;
my $pwconv = '/usr/sbin/pwconv';

# select home directory:
mkdir(Sauce::Config::homedir_base, Sauce::Config::perm_UserBaseDir)
	if (!-d Sauce::Config::homedir_base) ;
chmod(Sauce::Config::perm_UserBaseDir, Sauce::Config::homedir_base);
my $homedir = Sauce::Config::homedir_base . '/' . $obj->{name};

# build comment:
my $comment = $obj->{fullName} || $obj->{name};

# SJIS->EUC encode the comment.  For standard english alphanumeric
# strings, this is a noop, but for Chinese it will corrupt:
my $system_locale = I18n::i18n_getSystemLocale($cce);
if(I18n::i18n_getSystemLocale($cce) =~ /^ja/) {
  $comment = Jcode::convert($comment, 'euc', 'sjis');
}

# hacky workaround:
if (-e "/etc/group.lock") {
  $DEBUG && print STDERR "Breaking /etc/group.lock\n";
  unlink("/etc/group.lock"); # some other handler is leaving his around.
}

my @opts = (
  '-d', $homedir,
  '-c', $comment,
);

if (defined($new->{enabled})) {
  push (@opts, "-s", 
    ( $new->{enabled} ? Sauce::Config::default_shell : "/bin/false" ) );
}

# do we create or modify the user?
my $username = $old->{name} || $obj->{name};
my @pwent = getpwnam($username); # does user already exist?
if (!@pwent) {
  # create

  mysystem($useradd,
    '-k', '/etc/skel/user/'.I18n::i18n_getSystemLocale(), '-n', '-m',
    @opts, 
    $obj->{name});
  chmod(Sauce::Config::perm_UserDir, $homedir);
  {
	my $uid = getpwnam($username);
	my $gid = getgrnam('users');
	chown($uid,$gid, $homedir);
  }

} else {
  # modify
  mysystem($usermod,
    @opts,
    '-m', 
    '-l', $obj->{name},
    $username);
}

check_for_stupid_file('A');

if (defined($new->{password})) {
  system($pwconv);
}

check_for_stupid_file('B');

# verify that nothing went wrong, read uid and gid
setpwent;
my ($uid, $gid);
{
  my ($username, $cryptpw, $quota, $comment, $gcos, $dir,
      $shell, $expire);
  ($username, $cryptpw, $uid, $gid, $quota, $comment, $gcos, $dir,
      $shell, $expire) = getpwnam($obj->{name});
  if (!$username) {
    # could not create user, fail
    $cce->warn('[[base-user.failed-to-add-user,name=${username}]]');
    $cce->bye('FAIL');
    exit(1);
  }
}

check_for_stupid_file('C');

if (!@pwent) {
  # if we're doing a create, we also create all this junk:
  # the old sauce auto-created "Network Trash Folder"
  {
	  my $ntf = "${homedir}/Network Trash Folder";
	  mkdir($ntf, Sauce::Config::perm_UserDir);
	  chown($uid, $gid, $ntf);
	  chmod(Sauce::Config::perm_UserDir, $ntf);
  }

  # the old sauce auto-created "Private"
  {
	  my $ntf = "${homedir}/Private";
	  mkdir($ntf, Sauce::Config::perm_UserPrivDir);
	  chown($uid, $gid, $ntf);
	  chmod(Sauce::Config::perm_UserPrivDir, $ntf);
  }

  # create a default index.html file for the user's public directory
  {
    my $ntf = "${homedir}/web";
    mkdir ($ntf, 0755);
    chown($uid, $gid, $ntf);
    chmod(0755, $ntf);

    my $system_locale = I18n::i18n_getSystemLocale();
    $system_locale =~ s#en_US#en#;
    my $indexFile = '/etc/skel/user/'.$system_locale.'/web/index.html';
    if(-e $indexFile) {
      my $fin = new FileHandle("<$indexFile");
      my $fout = new FileHandle(">${homedir}/web/index.html");
      if ($fin && $fout) {
        my $fullname = $comment;
        while (defined($_ = <$fin>)) {
	  s/\[USER\]/$fullname/g;
	  s/\[USERNAME\]/$obj->{name}/g;
	  s/\[DOMAIN\]/$sysobj->{domainname}/g;
	  print $fout $_;
        }
        $fin->close();
        $fout->close();
      }
    } else {
      open(INDEX, ">${homedir}/web/index.html");
      print INDEX '<HTML><BODY>Default user page</BODY></HTML>';
      close(INDEX);
    }
    chown( (getpwnam($obj->{name}))[2], (getgrnam('users'))[2], "${homedir}/web/index.html");   
    chmod (0644, "${homedir}/web/index.html");
  }

}

# preserve email
if (defined($old->{name}) && defined($new->{name})) {
  system("/bin/mv",
    "/var/spool/mail/".$old->{name},
    "/var/spool/mail/".$new->{name});
}

# If this is the admin user, update the locale file that
# is used by the lcd
if ($obj->{name} eq "admin" && $new->{localePreference}
    && $new->{localePreference} ne "browser") {
    open(LOCALEFILE,">/usr/sausalito/locale");
    print LOCALEFILE "$new->{localePreference}";
    close(LOCALEFILE);

    # Set system "productLanguage" to match
    $cce->set($sysoid, "", {
      productLanguage => $new->{localePreference} } );
}

# update password
if (defined($new->{password})) {
  my ($crypt_pw, $md5_pw) = cryptpw($new->{password});

  if (!($new->{password})) {
	$crypt_pw = '*';
	$md5_pw = '*';
  }
  
  $cce->set($oid, "", { 
    crypt_password => $crypt_pw,
    md5_password => $md5_pw } );
} else {
  if ($cce->event_is_create()) {
	# create dummy passwords
	$cce->set($oid, "", {
		crypt_password => '*',
		md5_password => '*',
	} );
  }
}

# update workgroups
if ( (defined($old->{name}) && defined($new->{name})) )
{
	update_workgroups();
}

sub update_workgroups
{
	my @oids = $cce->find("Workgroup", { 'members' => $old->{name} });
	foreach my $oid (@oids) {
		my ($ok, $obj) = $cce->get($oid);
		my (@members) = $cce->scalar_to_array($obj->{members});
		@members = grep {$_ ne $old->{name}} @members;
		if (defined($new->{name})) {
			push(@members, $new->{name});
		}
		$cce->set($oid, "", { 'members' =>
			$cce->array_to_scalar(@members) } );
	}
}

# all done
$cce->bye("SUCCESS");
exit(0);


# that darn cat!
sub check_for_stupid_file
{
  my $checkpoint = shift;
  
  if (-e '/etc/group.lock') {
    unlink ('/etc/group.lock');
    $DEBUG && print STDERR "At checkpoint $checkpoint, /etc/group.lock was found.\n";
  }
}

sub sel
{
  return $_[int(rand(1+$#_))];
}

sub cryptpw
{
  my $pw = shift;
  my @saltchars = ('a'..'z','A'..'Z',0..9);
  srand();
  my $salt = sel(@saltchars) . sel(@saltchars);
  my $crypt_pw = crypt($obj->{password}, $salt);
  $salt = '$1$';
  for (my $i = 0; $i < 8; $i++) { $salt .= sel(@saltchars); }
  $salt .= '$';
  my $md5_pw = crypt($obj->{password}, $salt);
  return ($crypt_pw, $md5_pw);
}

sub fail()
{
  $cce->bye("FAIL");
  exit(1);
}

sub mysystem {
  $DEBUG && print STDERR "running: ",join(" ",map { "\"$_\"" } @_),"\n";
  system(@_);
}
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
