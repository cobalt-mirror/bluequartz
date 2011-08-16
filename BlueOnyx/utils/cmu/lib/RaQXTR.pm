# $Id: RaQXTR.pm 922 2003-07-17 15:22:40Z will $ 
# Copyright (c) 2000 Cobalt Networks, Inc.
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Written By: Jeff Bilicki

package RaQXTR;

BEGIN {
	require Exporter;

	use vars qw(@ISA @EXPORT @EXPORT_OK);
	@ISA    = qw(Exporter);
	@EXPORT = qw();

	use lib "/usr/cmu/perl";
}
	

####################
# migrateGetVsites #
# returns:  an array of fqdns
#
sub migrateGetVsites
{
	require Cobalt::Vsite;
	my @fqdns;

	foreach my $vhost (Cobalt::Vsite::vsite_list()) {
		next unless ($$vhost[1]);
		push(@fqdns,$$vhost[1]);
	}	
	return @fqdns;
}

#################
# vsiteRetrieve #
#
sub vsiteRetrieve 
{
	require Cobalt::Meta::vsite;
	require Cobalt::Vsite;
	require Cobalt::Email;

	my $group = shift || return;
	if(!getgrnam($group)) {
		warn "cannot find group: $group\n";
		return;
	}

	my $obj = new Cobalt::Meta::vsite;
	$obj->retrieve($group);
	my $objHash = {};
	foreach my $key (keys %{ $obj }) {
		$objHash->{$key} = $obj->{$key} if(defined($obj->{$key}));
	}

	# get email aliases
	my %em = Cobalt::Email::mail_virtuser_list_route($objHash->{fqdn});
	my @emailDomain = keys(%em);

	@{ $objHash->{emaildomain}->{domain} } = @emailDomain if(@emailDomain);
	
	# get web aliases
	my @webDomain = Cobalt::Vsite::vsite_list_alias($group);
	@{ $objHash->{webdomain}->{domain} } = @webDomain if (@webDomain);

	# place any remapping to be done here.
	delete $objHash->{modify};

	@Cobalt::Meta::vsite::emailSvrAli = ();
	@Cobalt::Meta::vsite::webSvrAli   = ();
	$Cobalt::Meta::vsite::fpxPassword = '';
	
	return($objHash);
}

##############
# vsiteGroup #
#              
sub vsiteGroup
{
	require Cobalt::Meta;

	my $siteName = shift || return;

	my ($ret) = Cobalt::Meta::query("type"  => "vsite",
		"keys"  => ["name"],
		"where" => ["name", "<>", "default",
		"and", "fqdn", "=", "$siteName"]);
    if($ret) {
		return $ret;
	} else {
		warn "ERROR: Cannot convert $siteName to groupName\n";
		return;
	}	
	
}

#############
# vsiteFqdn #
#              
sub vsiteFqdn
{
	require Cobalt::Meta;

	my $groupName = shift || return;

	my ($ret) = Cobalt::Meta::query("type"  => "vsite",
		"keys"  => ["fqdn"],
		"where" => ["name", "<>", "default",
		"and", "name", "=", "$groupName"]);
    if($ret) {
		return $ret;
	} else {
		warn "ERROR: Cannot convert $groupName to siteName\n";
	}	
}

#####################
# vsiteUserChildren #
#
sub vsiteUserChildren
{
	use Cobalt::Vsite;

	my $groupName = shift; 
	return unless($groupName);

	my @children;

	my @users = Cobalt::Vsite::vsite_get_users($groupName);
    foreach my $user (@users) {
		next unless ($user);
		push @children, $user;
	}
	return @children;
}

###################
# vsiteMLChildren #
# syntax:		RaQ4vsite::vsMLChildren();
#
sub vsiteMLChildren
{
	require Cobalt::Meta;
	use Cobalt::List;

	my $groupName = shift; 
	return unless($groupName);

	# get the fqdn
	my ($siteName) = Cobalt::Meta::query("type"  => "vsite",
		"keys"  => ["fqdn"],
		"where" => ["name", "<>", "default",
		"and", "name", "=", "$groupName"]);

	return Cobalt::List::list_lists($groupName,$siteName);	
}

############
# vsiteAdd #
# syntax:	$ret = RaQ4::vsiteAdd($vsiteHash);
# return:	name of the new group or error message
sub vsiteAdd
{
	require Cobalt::Meta::vsite;
	use Cobalt::Vsite;

	my $vsite = shift;
	my $vobj = new Cobalt::Meta::vsite;
	
	$vobj->hostname($vsite->{hostname});
	$vobj->domain($vsite->{domain});
	$vobj->fqdn($vsite->{hostname}.".".$vsite->{domain});
	$vobj->ipaddr($vsite->{ipaddr});
	$vobj->quota($vsite->{quota});
	$vobj->casp($vsite->{casp});
	$vobj->php($vsite->{php});

	($vsite->{maxusers}) ?
        ($vobj->{maxusers} = $vsite->{maxusers}) :
        ($vobj->{maxusers} = 25);

	($vsite->{cgi}) ?
		($vobj->{cgi} = $vsite->{cgi}) :
		($vobj->{cgi} = 'f');

	($vsite->{ssi}) ?
		($vobj->{ssi} = $vsite->{ssi}) :
		($vobj->{ssi} = 'f');

	($vsite->{fpx}) ?
		($vobj->{fpx} = $vsite->{fpx}) :
		($vobj->{fpx} = 'f');

	($vsite->{shell}) ?
		($vobj->{shell} = $vsite->{shell}) :
		($vobj->{shell} = 'f');

	($vsite->{ssl}) ?
		($vobj->{ssl} = $vsite->{ssl}) :
		($vobj->{ssl} = 'f');
		
	($vsite->{apop}) ? 
		($vobj->{apop} = $vsite->{apop}) :
		($vobj->{apop} = 'f');

	($vsite->{suspend}) ?
		($vobj->{suspend} = $vsite->{suspend}) :
		($vobj->{suspend} = 'f');

	($vsite->{ftpquota}) ?
		($vobj->{ftpquota} = $vsite->{ftpquota}) :
		($vobj->{ftpquota} = "20");

	($vsite->{ftpusers}) ?
		($vobj->{ftpusers} = $vsite->{ftpusers}) :
		($vobj->{ftpusers} = "20");

	# Ftp config
	if($vsite->{ftp} =~ /(t|true|yes|on|1)/oi) {
		$vobj->{ftp} = 't';
	} else { $vobj->{ftp} = 'f'; }

	if (ref($vsite->{webdomain}->{domain}) eq 'ARRAY') {
		$vobj->webServerAlias(@{ $vsite->{webdomain}->{domain} });
	} elsif(defined($vsite->{webdomain}->{domain})) {
		$vobj->webServerAlias(join(' ', $vsite->{webdomain}->{domain}));
	}

	if (ref($vsite->{emaildomain}->{domain}) eq 'ARRAY') {
		$vobj->emailServerAlias(@{ $vsite->{emaildomain}->{domain} });
	} elsif(defined($vsite->{emaildomain}->{domain})) {
		$vobj->emailServerAlias(join(' ', $vsite->{emaildomain}->{domain}));
	}
	unless(defined $vsite->{volume}) {
		$vobj->volume("home");
	}
	if($vobj->fpx) {
		my $fpxPass = "123456";
		$vsite->{fpxPasswd} = $fpxPass;
		$vobj->fpxPassword($fpxPass);
		my $fpxCnf = "/usr/local/frontpage/".$vsite->{name}.":80.cnf";
		unlink($fpxCnf) if(-f $fpxCnf);
	}

	my $err = 1;
	my ($ret,$info) = $vobj->add();
	if (!$ret) {
		warn "ERROR: ", join('\n', @$info), "\n";
		return;
	} elsif (scalar(@$info)) {
		warn "ERROR: ", join('\n', @$info), "\n";
		return;
	} 
	$vsite->{newGroup} = vsiteGroup($vsite->{fqdn});
	my $dflIndex = "/home/sites/".$vsite->{newGroup}."/web/index.html";
	unlink($dflIndex) if (-f $dflIndex);

	@Cobalt::Meta::vsite::emailSvrAli = ();
	@Cobalt::Meta::vsite::webSvrAli   = ();
	$Cobalt::Meta::vsite::fpxPassword = '';
	
	return($vsite->{newGroup});
}

###############
# vsiteBackup #
# syntax:       $conf = RaQXTR::vsiteBackup($groupName);
# returns a hash of scheduled backup parameters
sub vsiteBackup
{
	my $group = shift || return 0;

	my $cronBasedir = "/etc/cobalt/site-backups/";
	my $cronD = "/etc/cron.d/site-backups";


	my $cronDaily = $cronBasedir."daily/".$group;
	my $cronWeekly = $cronBasedir."weekly/".$group;
	my $cronMonthly = $cronBasedir."monthly/".$group;
	my $backupCgi = "/usr/local/sbin/site_backup.pl";

	my $cronFile;
    if(-e $cronDaily) { $cronFile = $cronDaily; } 
	elsif(-e $cronWeekly) { $cronFile = $cronWeekly; } 
	elsif(-e $cronMonthly) { $cronFile = $cronMonthly; } 
	else { return 0; } # no scheduled backup

	open(FH, "< $cronFile") ||
		die "RaQXTR::vsiteBackup Open failed: $cronFile: $!";
	my @data;
	while(my $line = <FH>) { push(@data, $line) }
	close(FH);

	my $conf;
	$conf->{name} = "backup";
	foreach my $d (@data) {
		if($d =~ /^\# (\w+): (\S+)$/) {
			$conf->{$1} = $2;
		} elsif($d =~ /^\# FIELD: (\w+): (\S+)$/) {
			$conf->{$1} = $2;
		} elsif ($d =~ /^$backupCgi/) {
			$conf->{backupCgi} = $backupCgi;
		} 
	}

	# get the time out of $cronD
	open(FH, "< $cronD") ||
		die "RaQXTR::vsiteBackup Open failed: $cronD: $!";
	@data = ();
	while(my $line = <FH>) { push(@data, $line) }
	close(FH);

	foreach my $c (@data) {
		if($c =~ /^(.*)( root $cronFile)/) { $conf->{cronTime} = $1 }
	}
	return($conf);
}


##################
# vsiteBackupAdd #
# syntax:	$ret = RaQ4vsite::vsBackupAdd($backupObj);
#
sub vsiteBackupAdd
{
	my $bTree = shift || return 0;
	my $group = shift || return 0;

	my $cronBasedir = "/etc/cobalt/site-backups/";
	my $cronD = "/etc/cron.d/site-backups";

	my $cronFile = $cronBasedir.$bTree->{frequency}."/".$group;


	my $schHeader = <<EOF;
#!/bin/sh
# 
# Scheduled backup for site $group
# Do not edit!  Changes will be overwritten by the user interface
#
# USER: $bTree->{REMOTE_USER}
# PROTOCOL: $bTree->{PROTOCOL}
# HOST: $bTree->{HOST}
# REMOTE_USER: $bTree->{REMOTE_USER}
# REMOTE_PATH: $bTree->{REMOTE_PATH}
# PASSWORD: $bTree->{PASSWORD}
$bTree->{backupCgi} $cronFile

# GUI reporting:
# FIELD: smb_share: $bTree->{smb_share}
# FIELD: smb_username: $bTree->{smb_username}
# FIELD: ftp_share: $bTree->{ftp_share}
# FIELD: ftp_username: $bTree->{ftp_username}
# FIELD: nfs_share: $bTree->{nfs_share}
# FIELD: frequency: $bTree->{frequency}

# Please add customizations below this line:

EOF
	
	open(FH, "> $cronFile") || 
		warn "ERROR: Open failed: $cronFile: $!";
	print FH $schHeader;
	close(FH);
	chown (0,0,$cronFile);
	chmod (0700, $cronFile);

	my $line = $bTree->{cronTime}." root ".$cronFile."\n";
	open(FH, ">> $cronD")  ||
		warn "ERROR: Open failed: $cronD: $!";
	print FH $line;
	close(FH);

	return 1;
}

################
# userRetrieve #
# syntax:	RaQ4::usrRetrieve($userName);
# returns:	User data structure
#
sub userRetrieve 
{
	require Cobalt::Meta;

	my $user = shift || return;
	setpwent;
	if(!(getpwnam($user))[0]) {
		warn "cannot find user: $user\n";
		return;
	}

	my $obj = Cobalt::Meta->create("type" => "users");
	$obj->retrieve($user);
	my $objHash = {};
	foreach my $key (keys %{ $obj }) {
		$objHash->{$key} = $obj->{$key} if(defined($obj->{$key}));
	}
	if(defined $objHash->{aliases}) {
		my $alii = $objHash->{aliases};
		delete $objHash->{aliases};
		# I love remaping data types on the fly
		@{ $objHash->{aliases}->{alias} } = split(/\s/, $alii);
	}
	# handle the vacation stuff
	my $vaca = RaQUtil::getVacation($user);
	if($vaca) { $objHash->{vacationmsg} = $vaca; }

	delete $objHash->{modify};
	return($objHash);
}

###########
# userAdd #
# syntax:	$ret = RaQ4user::userAdd($userObj);
sub userAdd
{
	require Cobalt::Meta;
	use Cobalt::User;
	use Cobalt::List;
	use Cobalt::Locale;
	use Cobalt::Vacation;

	my $user = shift;
	my $siteGroup = shift || return 0;
	my $fqdn = vsiteFqdn($siteGroup);
	
	# The default passwd all acounts are create w/ b4 the 
	# crypt/md5 stuff is added to /etc/shadow
	my $dflPasswd = "userDflPasswd";

	my $lobj = Cobalt::Locale->new();
	my $uobj = Cobalt::Meta->create("type" => "users");
	$uobj->put(
		name		=>	$user->{name},
		fullname	=>	$user->{fullname},
		group		=>	$siteGroup,
		password1	=>	$dflPasswd,
		password2	=>	$dflPasswd,
		quota		=>	$user->{quota}
	);

	$uobj->{altname} = $user->{altname} if($user->{altname});

	if(ref($user->{aliases}->{alias}) eq 'ARRAY') {
		$uobj->{aliases} = join(' ', @{ $user->{aliases}->{alias} });
	} elsif(exists($user->{aliases}->{alias})) {
		$uobj->{aliases} =  $user->{aliases}->{alias};
	}

	($user->{suspend}) ? 
		($uobj->{suspend} = $user->{suspend}) :
		($uobj->{suspend} = "f");

	($user->{vacation}) ? 
		($uobj->{vacation} = $user->{vacation}) :
		($uobj->{vacation} = "f");

	($user->{apop}) ? 
		($uobj->{apop} = $user->{apop}) :
		($uobj->{apop} = "f");

	($user->{shell}) ?
		($uobj->{shell} = $user->{shell}) :
		($uobj->{shell} = "f");

	($user->{admin}) ?
		($uobj->{admin} = $user->{admin}) :
		($uobj->{admin} = "f");

	($user->{forward} !~ /^(f|false|no|off|0)?$/oi) ?
		($uobj->{forward} = $user->{forward}) :
		($uobj->{forward} = "f");

	if($user->{fpx} =~ /(t|true|yes|on|1)/oi) {
		$uobj->{fpx} = "t";
	} else { $uobj->{fpx} = "f"; }

	my $err = 1;	
	my $ret = Cobalt::User::site_user_add($uobj);
	if($ret) {
		warn "ERROR: user add ", $user->{name}, " returned: ", $ret, "\n";	
		$err = 0;
	}

	my $fnEnc = $lobj->getProperty("fullname_enc");
	if ($fnEnc) {
		fullname_enc($user->{name},$fnEnc);
	}
	Cobalt::Meta::set_encoding($Cobalt::Meta::client_encodings{$lobj->getLocale});

	unless($uobj->{forward} eq 'f') {
  		Cobalt::List::alias_set($user->{name}, $user->{forward});
	}
	if(defined($user->{vacationmsg})) {
		Cobalt::Vacation::vacation_set_message($user->{name}, $user->{vacationmsg});
	}
	if($user->{vacation} eq 't') {
		Cobalt::Vacation::vacation_set_on($user->{name}, 1, $fqdn);
	}

	my $dflIndex = "/home/sites/$siteGroup/users/".$user->{name}."/web/index.html";
	unlink($dflIndex) if (-f $dflIndex);

	return($err);
}

################
# fullname_enc #
# user to encode the fullname in the /etc/password file to
# the proper value
sub fullname_enc
{
	use Cobalt::Util;

    my $name = shift;
    my $enc = shift;
	my $Passwd = "/etc/passwd"; 

	if (!$enc) {
		return;
	} else {
		my $uObj = Cobalt::Meta->new("type" => "users");
		Cobalt::Meta::set_encoding($enc);
		$uObj->retrieve($name);
		my $FN_ENC = $uObj->get("fullname");
		if (!$FN_ENC) {
			return "cannot get username: $name";
		}
		FILE_lock_rw("$Passwd", *PWIN, *PWOUT) ||
			return "cannot lock file $Passwd";
		while (<PWIN>) {
			s/(^$name:[^:]+:[^:]+:[^:]+):[^:]+:(.+)/$1:$FN_ENC:$2/;
			print PWOUT;
		}
		FILE_unlock_rw("$Passwd", *PWIN, *PWOUT) ||
			return "cannot unlock $Passwd";
	}
	return "fullname_enc_ok";
}   


####################
# mailListRetrieve #
# arguments:	list name, group name of vsite, fqdn of vsite
# returns:	data structure of mailing list info 
#
sub mailListRetrieve
{
	require Cobalt::List;

	my $list = shift;
	my $groupName = shift;
	my $siteName = shift || return;

	my $listHash = {};

    my ($dir) = Cobalt::List::list_virtualize($groupName, $siteName);
    my (@intmembers,@extmembers);
    $list =~ tr[A-Z][a-z];      # lowercase the characters
    if (-f "$dir/lists/$list") {
        open (LIST, "<$dir/lists/$list") ||
			warn "ERROR: Cannot read from $dir/lists/$list";
        while (my $line = <LIST>) {
            chomp($line);
	    	my ($uname,$fqdn) = split(/\@/,$line);
            if ($fqdn eq $siteName) {
            	push @intmembers, $uname;
			} else {
				push @extmembers, $line;
			}
        }
        close LIST;
		@{ $listHash->{extRecips}->{recip} } = @extmembers if (@extmembers);
		@{ $listHash->{intRecips}->{recip} } = @intmembers if (@intmembers);
    }

	$listHash->{mlPasswd} = Cobalt::List::list_config_getpassword($groupName, $siteName, $list);
	$listHash->{subscription} = Cobalt::List::list_config_getstate($groupName, $siteName, $list);
	$listHash->{restrict} = Cobalt::List::list_config_getrestrict($groupName, $siteName, $list);

	return($listHash);

}

###############
# mailListAdd #
# syntax:	RaQ4::mailListAdd($hash);
# arguments:	group name of vsite, fqdn of vsite, mail list data structure
# side effects:	adds mailing list to the system
#
sub mailListAdd 
{
	require Cobalt::List;

	my $groupName = shift;
	my $fqdn = shift;
	my $mail = shift || return 0;
	my $list = $mail->{name} || return 0;
	

	my @recips = ();
	if(defined $mail->{intRecips}) {
		foreach my $recip (@{ $mail->{intRecips}->{recip} }) {
			push(@recips, $recip."\@".$fqdn);
		}
	}
	if(defined $mail->{extRecips}) {
		foreach my $recip (@{ $mail->{extRecips}->{recip} }) {
			push(@recips, $recip);
		}
	}
	# used for debugging
	#my $cr = "ADD mailList: ".$groupName.":".$fqdn.":".$list.":".@recips."\n";
	my $ret = Cobalt::List::list_create($groupName, $fqdn, $list, @recips);

	if(defined($mail->{mlPasswd})) {
		$ret .= Cobalt::List::list_config_password($groupName, 
			$fqdn, $list, $mail->{mlPasswd});
	}

	# Take care of subscription
	if( $mail->{subscription} =~ /(t|true|yes|on|1)/oi ) {
		Cobalt::List::list_config_open($groupName, $fqdn, $list);
	} else {
		Cobalt::List::list_config_closed($groupName, $fqdn, $list);
	}

	# restricted posting?
	if ( $mail->{restrict} =~ /(t|true|yes|on|1)/oi ) {
		Cobalt::List::list_config_restrict($groupName, $fqdn, $list);
	} else {
		Cobalt::List::list_config_norestrict($groupName, $fqdn, $list);
	}
	return $ret;
}

1;
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
