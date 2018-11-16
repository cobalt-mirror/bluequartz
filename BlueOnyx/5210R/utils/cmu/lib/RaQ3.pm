# $Id: RaQ3.pm 922 2003-07-17 15:22:40Z will $ 
# Copyright (c) 2000 Cobalt Networks, Inc.
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# Written By: Jeff Bilicki

package RaQ3;

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
	require Cobalt::Meta;
	require Cobalt::Vsite;
	require Cobalt::Email;

	my $group = shift || return;
	if(!getgrnam($group)) {
		warn "cannot find group: $group\n";
		return;
	}

	my $obj = Cobalt::Meta->create(type => 'vsite');
	$obj->retrieve($group);
	my $objHash = {};
	foreach my $key (keys %{ $obj }) {
		$objHash->{$key} = $obj->{$key};
	}

	if($vobj->{emaildomain} =~ /^(t|true|yes|on|1)$/oi) {
		$objHash->{emaildomain} = $vobj->{domain};
	}
	if($vobj->{webdomain} =~ /^(t|true|yes|on|1)$/oi) {
		$objHash->{webdomain} = $vobj->{domain};
	}


	# place any remapping to be done here.
	delete $objHash->{modify};

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
# syntax:		RaQ3vsite::vsMLChildren();
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
# syntax:	$ret = RaQ3::vsiteAdd($vsiteHash);
# return:	name of the new group or error message
sub vsiteAdd
{
	use Cobalt::Admserv;
	use Cobalt::Email;
	use Cobalt::Fpx;
	use Cobalt::Ftp;
	use Cobalt::Group;
	use Cobalt::List;
	use Cobalt::Meta;
	use Cobalt::Netutil;
	use Cobalt::Vsite;
	use Cobalt::Product;

	my $vsite = shift;
	my $siteName = $vsite->{name} || return;	

	my $vobj = new Cobalt::Meta("type" => "vsite");
	
    $vobj->{hostname} = $vsite->{hostname};
    $vobj->{domain} = $vsite->{domain};
    $vobj->{fqdn} = $siteName;
    $vobj->{ipaddr} = $vsite->{ipaddr};
    $vobj->{quota} = $vsite->{quota};

	($vsite->{maxusers}) ?
        ($vobj->{maxusers} = $vsite->{maxusers}) :
        ($vobj->{maxusers} = 25);

	($vsite->{cgi}) ?
		($vobj->{cgi} = $vsite->{cgi}) :
		($vobj->{cgi} = 'off');

	($vsite->{ssi}) ?
		($vobj->{ssi} = $vsite->{ssi}) :
		($vobj->{ssi} = 'off');

	($vsite->{fpx}) ?
		($vobj->{fpx} = $vsite->{fpx}) :
		($vobj->{fpx} = 'off');

	($vsite->{shell}) ?
		($vobj->{shell} = $vsite->{shell}) :
		($vobj->{shell} = 'off');

	($vsite->{ssl}) ?
		($vobj->{ssl} = $vsite->{ssl}) :
		($vobj->{ssl} = 'off');
		
	($vsite->{apop}) ? 
		($vobj->{apop} = $vsite->{apop}) :
		($vobj->{apop} = 'off');

	($vsite->{suspend}) ?
		($vobj->{suspend} = $vsite->{suspend}) :
		($vobj->{suspend} = 'off');

	($vsite->{ftpquota}) ?
		($vobj->{ftpquota} = $vsite->{ftpquota}) :
		($vobj->{ftpquota} = "20");

	($vsite->{ftpusers}) ?
		($vobj->{ftpusers} = $vsite->{ftpusers}) :
		($vobj->{ftpusers} = "20");

	# Ftp config
	if($vsite->{ftp} =~ /(t|true|yes|on|1)/oi) {
		$vobj->{ftp} = 'on';
	} else { $vobj->{ftp} = 'off'; }

	($vsite->{webdomain}) ?
		($vobj->{webdomain} = $vsite->{webdomain}) :
		($vobj->{webdomain} = 'off');

	($vsite->{emaildomain}) ?
		($vobj->{emaildomain} = $vsite->{emaildomain}) :
		($vobj->{emaildomain} = 'off');

	if($vobj->{fpx} =~ /(t|true|yes|on|1)/oi) {
		my $fpxCnf = "/usr/local/frontpage/".$vsite->{name}.":80.cnf";
		unlink($fpxCnf) if(-f $fpxCnf);
	}
	$ret = vsiteCreate($vobj);
	if($ret == 0) { return; }

	$vsite->{newGroup} = vsiteGroup($vsite->{fqdn});
	my $dflIndex = "/home/sites/".$vsite->{newGroup}."/web/index.html";
	unlink($dflIndex) if (-f $dflIndex);

	return($vsite->{newGroup});
}

###############
# vsiteBackup #
# syntax:       $conf = RaQ3::vsiteBackup($groupName);
# returns a hash of scheduled backup parameters
# 
sub vsiteBackup
{
	my $groupName = shift || return 0;

	my $Cron_basedir = "/etc/cron.";
	my $AdminCGIs_dir =  "/usr/admserv/cgi-bin/.cobalt";

	my $crondaily = $Cron_basedir."daily";
	my $cronweekly = $Cron_basedir."weekly";
	my $cronmonthly = $Cron_basedir."monthly";

	my $sch = "/scheduled-backup.".$groupName;

	my $cronFile;
    if( -e $crondaily.$sch ) { 
		$cronFile = $crondaily.$sch; 
	} elsif( -e $cronweekly.$sch ) { 
		$cronFile = $cronweekly.$sch; 
	} elsif( -e $cronmonthly.$sch ) {
		$cronFile = $cronmonthly.$sch; 
	} else {
		# no scheduled backup
		return 0;
	}

	open(FH, "< $cronFile") ||
		die "RaQ3vsite::vsBackup Open failed: $cronFile: $!";
	my @data;
	while(my $line = <FH>) {
		push(@data, $line);
	}
	close(FH);

	my $conf;
	$conf->{name} = "backup";
	for (@data) {
		if( /^\# (\w+)=(\S+)/ ) {
			my $value = $2;
			if ($1 eq "scheduled") {
				$value = "daily" if ($2 eq "100");
				$value = "weekly" if ($2 eq "200");
				$value = "monthly" if ($2 eq "300");
			}
			$conf->{$1} = $value;
		} elsif ( /(^$AdminCGIs_dir.*)(password)(\s')(\S+)(')/) {
			$conf->{$2} = $4;
		} 
	}
	return($conf);
}


##################
# vsiteBackupAdd #
# syntax:	$ret = RaQ3::vsiteBackupAdd($backupObj);
sub vsiteBackupAdd
{
	my $bTree = shift || return 0;
	my $group = shift || return 0;

	my $Cron_basedir = "/etc/cron.";
	my $AdminCGIs_dir = "/usr/admserv/cgi-bin/.cobalt";
	my $crondaily = $Cron_basedir.'daily';
	my $cronweekly = $Cron_basedir.'weekly';
	my $cronmonthly = $Cron_basedir.'monthly';
	my $progName = $AdminCGIs_dir."/backup/backup.cgi";

	# remap the group if needed 
	$bTree->{target} = $group;

	my $sch = "/scheduled-backup.".$bTree->{target};
	my $cronFile = $Cron_basedir.$bTree->{scheduled}.$sch;

	@crons = ( $crondaily, $cronweekly, $cronmonthly );
	foreach my $cron (@crons) {
		if(-e $cron.$sch) {
			warn "Scheduled backup already exists: $cron.$sch, deleting";
			unlink "$cron.$sch";
		}
	}
	
	my $inc = "";
	my $pass = "";
    if($bTree->{increment}) { 
		$inc = ' -i '.$bTree->{increment}; 
	} 
    if($bTree->{password}) { 
		$pass = " --password \'".$bTree->{password}."\'"; 
	}
    my $cmd = $progName." -b ".$bTree->{backupset}." -t ".
			$bTree->{target}." -p ".$bTree->{protocol}.
           " -r "."\"".$bTree->{resource}."\""." -s 420". 
			$inc.$pass."\n";

	# this is silly
	my $schConv;
	if($bTree->{scheduled} eq "daily") {
		$schConv = 100;
	} elsif($bTree->{scheduled} eq "weekly") {
		$schConv = 200;
	} elsif($bTree->{scheduled} eq "monthly") {
		$schConv = 300;
	} else {
		$schConv = 420;
	}

	my $schHeader = <<EOF;
# Cobalt Networks
# Scheduled Backup
#
# backupset=$bTree->{backupset}
# target=$bTree->{target}
# protocol=$bTree->{protocol}
# resource=$bTree->{resource}
# increment=$bTree->{increment}
# scheduled=$schConv

EOF
	
	open(FH, "> $cronFile") || 
		warn "ERROR: Open failed: $cronFile: $!";
	print FH $schHeader;
	print FH $cmd;
	close(FH);
	
	chown (0,0,$cronFile);
	chmod (0700, $cronFile);
	return 1;
}

################
# userRetrieve #
# syntax:	RaQ3::usrRetrieve($userName);
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
	if($objHash->{aliases}) {
		my $alii = $objHash->{aliases};
		undef $objHash->{aliases};
		# I love remaping data types on the fly
		@{ $objHash->{aliases}->{alias} } = split(/\s/, $alii) if($alii);
	}
	# handle the vacation stuff
	my $vaca = RaQUtil::getVacation($user);
	if($vaca) { $objHash->{vacationmsg} = $vaca; }

	delete $objHash->{modify} if defined($objHash->{modify});
	return($objHash);
}

###########
# userAdd #
# syntax:	$ret = RaQ3user::userAdd($userObj);
sub userAdd
{
	require Cobalt::Meta;
	use Cobalt::User;
	use Cobalt::List;
	use Cobalt::Locale;
	use Cobalt::Vacation;

	my $user = shift;
	my $siteGroup = shift || return;
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
		quota		=>	$user->{quota},
	);

	$uobj->{altname} = $user->{altname} if(defined($user->{altname}));

	if(ref($user->{aliases}->{alias}) eq 'ARRAY') {
		$uobj->{aliases} = join(' ', @{ $user->{aliases}->{alias} });
	} elsif(exists($user->{aliases}->{alias})) {
		$uobj->{aliases} =  $user->{aliases}->{alias};
	}

	($user->{suspend}) ? 
		($uobj->{suspend} = $user->{suspend}) :
		($uobj->{suspend} = 'off');

	($user->{vacation}) ? 
		($uobj->{vacation} = $user->{vacation}) :
		($uobj->{vacation} = 'off');

	($user->{apop}) ? 
		($uobj->{apop} = $user->{apop}) :
		($uobj->{apop} = 'off');

	($user->{shell}) ?
		($uobj->{shell} = $user->{shell}) :
		($uobj->{shell} = 'off');

	($user->{admin}) ?
		($uobj->{admin} = $user->{admin}) :
		($uobj->{admin} = 'off');

	($user->{forward} !~ /^(f|false|no|off|0)?$/oi) ?
		($uobj->{forward} = $user->{forward}) :
		($uobj->{forward} = 'off');

	if($user->{fpx} =~ /(t|true|yes|on|1)/oi) {
		$uobj->{fpx} = 'on';
		#$uobj->{quota} = cmuFpx::fpxQuota($user);
	} else {
		$uobj->{fpx} = 'off';
	}

	my $ret = Cobalt::User::site_user_add($uobj);
	if($ret) {
		warn "ERROR: user add ", $user->{name}, " returned: ", $ret, "\n";	
		return 0;
	}

	my $fnEnc = $lobj->getProperty("fullname_enc");
	if ($fnEnc) {
		fullname_enc($user->{name},$fnEnc);
	}
	Cobalt::Meta::set_encoding($Cobalt::Meta::client_encodings{$lobj->getLocale});

	if($uobj->{forward} ne 'off') {
  		Cobalt::List::alias_set($user->{name}, $user->{forward});
	}
	if(defined($user->{vacationmsg})) {
		Cobalt::Vacation::vacation_set_message($user->{name}, $user->{vacationmsg});
	}
	if($user->{vacation} eq 'on') {
		Cobalt::Vacation::vacation_set_on($user->{name}, 1, $fqdn);
	}

	my $dflIndex = "/home/sites/$siteGroup/users/".$user->{name}."/web/index.html";
	unlink($dflIndex) if (-f $dflIndex);

	return 1;
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
# syntax:	RaQ3::mailListAdd($hash);
# arguments:	group name of vsite, fqdn of vsite, mail list data structure
# side effects:	adds mailing list to the system
#
sub mailListAdd 
{
	require Cobalt::List;

	my $groupName = shift;
	my $fqdn = shift;
	my $mail = shift || return;
	my $list = $mail->{name} || return;
	
	my @recips;
	foreach my $recip (@{ $mail->{intRecips}->{recip} }) {
		push(@recips, $recip."\@".$fqdn);
	}

	foreach my $recip (@{ $mail->{extRecips}->{recip} }) {
		push(@recips, $recip);
	}

	# used for debugging
	my $cr = "ADD mailList: ".$groupName.":".$fqdn.":".$list.":";

	my $ret = Cobalt::List::list_create($groupName, $fqdn, $list, @recips);

	# Set the password
	if(defined($mail->{mlPasswd})) {
		$ret = Cobalt::List::list_config_password($groupName, 
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
	return 1;
}

sub vsiteCreate
# Argument: vsite obj
# Side Effects: Creates the Vsite
# Returns: 1 for success, 0 for failure
{
	my $obj = shift;
	my $err;
	my $info;
	my $ret;

	my $hostName=$obj->get('hostname');
	my $domain=$obj->get('domain');
	my $ipAddr=$obj->get('ipaddr');
	my $emailDomain=$obj->get('emaildomain');
	my $webDomain=$obj->get('webdomain');
	my $diskQuota=$obj->get('quota');
	my $maxUsers=$obj->get('maxusers');
	my $shell=$obj->get('shell');
	my $cgi=$obj->get('cgi');
	my $ssi=$obj->get('ssi');
	my $ssl=$obj->get('ssl');
	my $fpx=$obj->get('fpx');
	my $apop=$obj->get('apop');
	my $ftp=$obj->get('ftp');
	my $ftpQuota=$obj->get('ftpquota');
	my $ftpUsers=$obj->get('ftpusers');

	my $host = $hostName;
	$hostName = $hostName.".".$domain;

	# name-based generation for filesystem readability
	my $groupName = Cobalt::Vsite::vsite_gen_name( $hostName );

	# logs rotate in MB not blocks
	my $logRotateLimit=int($diskQuota/10);
	$logRotateLimit=1 unless ($logRotateLimit);

	# convert from megabyte to block
	my $quota = $diskQuota;
	$diskQuota*=1048576/1024;
	#my $fquota = $ftpQuota;
	#$ftpQuota*=1048576/1024;

	# make the group for the site
	$ret=Cobalt::Group::group_add( $groupName, $diskQuota );
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}
	Cobalt::Layout::layout_siteManage($groupName,$hostName);
	$ret = Cobalt::Admserv::admserv_set_access( "$AdminPages_dir/siteManage/$groupName", 'require', 'group', $groupName );
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}
	# substitute variable names
	my $idxin = "$Htgroup_dir/$groupName/$Webdir/index.html";
	my $idxout = "/etc/locks/index.html.$groupName";
	if (-f $idxin) {
	    open(IN,"$idxin");
	    open(OUT,">$idxout");
	    while( <IN> ) {
			s/\[DOMAIN\]/$hostName/go;
			print OUT;
	    }
	    close( IN );
	    close( OUT );
	    system( "mv -f $idxout $idxin" );
	    chown($WWWuid,(getgrnam($groupName))[2],"$Htgroup_dir/$groupName/$Webdir/index.html");
	    chmod(0664,"$Htgroup_dir/$groupName/$Webdir/index.html"); 
	}
	
	# setup a virtual list server
	$ret=Cobalt::List::list_create_virtual ($groupName, $hostName);
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}

	# Missing optional webserver admin and subnet mask
	$ret=Cobalt::Vsite::vsite_add( $ipAddr, $hostName, $groupName );
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}

	# Rotate split log fragments for the site
	$ret=Cobalt::Vsite::vsite_set_logrotate($groupName,$logRotateLimit);
	
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}
	if ($webDomain eq 'on') {
	    # add a ServerAlias directive to the vhost section
	    $ret = Cobalt::Vsite::vsite_add_alias($groupName, $domain);
	    if( $ret!~/^2/ ) {
			$info.=substr( $ret, 4 ).'\n';
			$err=1;
	    }
	}
	if ($emailDomain eq 'on') {
	    $ret=Cobalt::Email::mail_virtuser_add_route($domain, $hostName);
	    if( $ret!~/^2/ ) {
			$info.=substr( $ret, 4 ).'\n';
			$err=1;
	    }
	    $ret=Cobalt::Email::mail_add_relay( $domain );
	    	if( $ret!~/^2/ ) {
			$info.=substr( $ret, 4).'\n';
			$err=1;
	    }
	    $ret=Cobalt::Email::mail_add_cw( $hostName, $domain );
	} else {
	    $ret=Cobalt::Email::mail_add_cw($hostName);
	    $emailDomain='off';
	}
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}
	# add IP address to relay list
	$ret=Cobalt::Email::mail_add_relay( $ipAddr );
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}
	Cobalt::Email::mail_sendmail_hup();

	# config ftp
	if ($ftp eq 'on') {
	    $ret=Cobalt::Ftp::ftp_config($ipAddr,1,1,$groupName,$ftpUsers,$ftpQuota);
	} else {
	    $ret=Cobalt::Ftp::ftp_config($ipAddr,1,0,$groupName);
	}
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}

	# config cgi
	if( $cgi eq 'on' ) { $ret=Cobalt::Vsite::vsite_set_cgis( $groupName, 1 ); }
	else { $ret=Cobalt::Vsite::vsite_set_cgis( $groupName, 0 ); }
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}
	
	# config ssi
	if( $ssi eq 'on' ) { $ret=Cobalt::Vsite::vsite_set_ssi( $groupName, 1 ); }
	else { $ret=Cobalt::Vsite::vsite_set_ssi( $groupName, 0 ); }
	if( $ret!~/^2/ ) {
	    $info.=substr( $ret, 4 ).'\n';
	    $err=1;
	}
	
	# config apache's ssl
	Cobalt::Vsite::vsite_set_sslconf($groupName, $ssl);

	# config fpx
	if($fpx eq 'on')
	{
	    $ret=Cobalt::Fpx::fpx_set_web( $groupName, 1 );
	    if( $ret!~/^2/ ) {
			$info.=substr( $ret, 4 ).'\n';
			$err=1;
	    }
	}
	Cobalt::Http::http_restart();

	# Build the user defaults
	my $default = Cobalt::Meta->create("type" => "vsite");
	$default->retrieve("default");
	
	my $def_user_quota = $default->get("user_quota");
	my $def_user_fpx = $default->get("user_fpx");
	my $def_user_shell = $default->get("user_shell");
	my $def_user_apop = $default->get("user_apop");
	my $def_user_namegen = $default->get("user_namegen");
	my $def_userlist_sort = $default->get("userlist_sort");
	my $def_userlist_range = $default->get("userlist_range");

	$obj->put("type" => "vsite",
		  "name" => $groupName,
		  "gid" => (getgrnam($groupName))[2],
		  "hostname" => $host,
		  "domain" => $domain,
		  "fqdn" => $hostName,
		  "ipaddr" => $ipAddr,
		  "bwlimit" => 'off',
		  "emaildomain" => $emailDomain,
		  "webdomain" => $webDomain,
		  "quota" => $quota,
		  "maxusers" => $maxUsers,
		  "shell" => $shell,
		  "cgi" => $cgi,
		  "ssi" => $ssi,
		  "ssl" => $ssl,
		  "fpx" => $fpx,
		  "apop" => $apop,
		  "suspend" => 'off',
		  "ftp" => $ftp,
		  "ftpusers" => $ftpUsers,
		  "ftpquota" => $ftpQuota,
		  "user_quota" => $def_user_quota,
		  "user_fpx" => $def_user_fpx,
		  "user_shell" => $def_user_shell,
		  "user_apop" => $def_user_apop,
		  "user_namegen" => $def_user_namegen,
		  "userlist_sort" => $def_userlist_sort,
		  "userlist_range" => $def_userlist_range);
	
	# hmmmm
	if(exists($obj->{volume})) {	
		delete $obj->{volume};
	}
	if ($err) {
		warn "ERROR: importing vsite $hostName: $info\n";
		return 0;
	} else { $obj->save(); }
	return 1;
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
