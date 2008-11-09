# RaQ2mailList.pm
# $Id: RaQ2.pm 922 2003-07-17 15:22:40Z will $ 
# Copyright (c) 2000 Cobalt Networks, Inc.
# Written By: Jeff Bilicki

package RaQ2;

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

##############
# vsiteGroup #
#              
sub vsiteGroup
{
	use Cobalt::Vsite;

	my $siteName = shift || return;
	my $groupName;

	my @vhosts =  Cobalt::Vsite::vsite_list();
	foreach my $vhost (@vhosts) {
		if($siteName eq $$vhost[1]) {
			$groupName = $$vhost[2];
		}
	}
    if($groupName) {
		return $groupName;
	} else {
		warn "ERROR: Cannot convert $siteName to a group name\n";
		return;
	}	
}

#####################
# vsiteUserChildren #
#
sub vsiteUserChildren
{
	require Cobalt::List;
	import Cobalt::List qw( list_get_mem );

	my $groupName = shift; 
	return unless($groupName);

	my @children;

	my @users = list_get_mem($groupName.'-users');
    foreach my $user (@users) {
		next unless ($user);
		next if($user eq "admin");
		push @children, $user;
	}
	return @children;
}

###################
# vsiteMLChildren #
# syntax:		RaQ2::vsiteMLChildren();
#
sub vsiteMLChildren
{
	require Cobalt::List;
	import Cobalt::List qw( list_lists );

	require Cobalt::Vsite;
	import Cobalt::Vsite qw( vsite_get_bygroup );

	my $groupName = shift; 
	return unless($groupName);

	# get the fqdn
	my $siteName = (vsite_get_bygroup($groupName))[1];
	my @lists;
	foreach my $list (Cobalt::List::list_lists($groupName,$siteName)) {
		next if ($list eq "site-adm");
		next if ($list eq $groupName);
		next if ($list eq $groupName."-users");
		if($list =~ /^(\S+)(_$groupName)/) {
			push @lists, $1;
		} 
	}
	return @lists;

}

#################
# vsiteRetrieve #
#
sub vsiteRetrieve 
{
	use Cobalt::Vsite;
	use Cobalt::Group;
	use Cobalt::Fpx;
	use Cobalt::Ftp;

	my $groupName = shift || return;
	my $DB_dir = "/var/cobalt/uidb";
	my $objHash = {};

	my ($ipaddr,$siteName) = (vsite_get_bygroup($groupName))[0,1];
	$objHash->{ipaddr} = $ipaddr;
	$objHash->{fqdn} = $siteName;
	$objHash->{name} = $groupName;
	if($siteName =~ /^(\w+)\.(.*)/) {
		$objHash->{hostname} = $1;
		$objHash->{domain} = $2;
	}
	my $quota = (group_get_quota($groupName))[1];
	$quota*=1024/1048576;
	$objHash->{quota} = $quota;

	my %UIDB;
	tie(%UIDB,'DB_File',$DB_dir,O_RDWR|O_CREAT,0600) 
		|| die "Cannot open database: $!";
	$objHash->{maxusers} = $UIDB{'maxUsers-'.$groupName};
	my $shell = $UIDB{'shell-'.$groupName};
	my $emaildomain = $UIDB{'emailDomain-'.$groupName};
	untie(%UIDB);


	if ($emaildomain =~ /^(t|true|yes|on|1)$/oi) {
		$objHash->{emaildomain} = 'on';
	}	
	$objHash->{shell} = 'on' if ($shell =~ /^(t|true|yes|on|1)$/oi);
	$objHash->{shell} = 'off' if ($shell =~ /^(f|false|no|off|0)?$/oi);
		

	my $cgi = vsite_get_cgis($groupName);
	$objHash->{cgi} = 'on' if ($cgi =~ /^(t|true|yes|on|1)$/oi);
	$objHash->{cgi} = 'off' if ($cgi =~ /^(f|false|no|off|0)?$/oi);

	my $ssi = vsite_get_ssi($groupName);
	$objHash->{ssi} = 'on' if ($ssi =~ /^(t|true|yes|on|1)$/oi);
	$objHash->{ssi} = 'off' if ($ssi =~ /^(f|false|no|off|0)?$/oi);

	$fpx = fpx_get_web($groupName);
	$objHash->{fpx} = 'on' if ($fpx =~ /^(t|true|yes|on|1)$/oi);
	$objHash->{fpx} = 'off' if ($fpx =~ /^(f|false|no|off|0)?$/oi);

	my ($anon, $ftpusers, $ftpquota) = ftp_get_anonymous($groupName);
	if($anon == 1) {
		$objHash->{ftp} = 'on';
		$objHash->{ftpusers} = $ftpusers;
		$ftpquota*=1024/1048576;
		$objHash->{ftpquota} = $ftpquota;
	}
	return $objHash;
}

################
# userRetrieve #
# syntax:	RaQ2::usrRetrieve($userName);
# returns:	User data structure
#
sub userRetrieve 
{
	use Cobalt::User;
	use Cobalt::List;
	use Cobalt::Fpx;
	use Cobalt::Vacation;
	use Cobalt::Email;
	use Cobalt::Vsite;
	use Cobalt::Language;

	my $userName =  shift || return;
	my $groupName = (user_list_site($userName))[1];
	my $objHash = {};

	my ($fullName, $quota, $shell) = (user_show($userName,0))[1,2,4];

	$objHash->{fullname} = $fullName;

	$quota*=1024/1048576;
	$objHash->{quota} = $quota;

	$objHash->{shell} = 'on' if ($shell =~ /^\/bin\/(bash|sh|tcsh|csh)$/oi);
	$objHash->{shell} = 'off' if ($shell =~ /^\/bin\/badsh$/oi);

	my @for = alias_get_vacationless($userName);
	if($for[0] =~ /^[134]\S*\s/ || !@for) {
		$objHash->{forward}	= 'off';
	} else {
		$objHash->{forward}	= join ',', @for;
	}

	my $fpx = fpx_get_web($userName);
	$objHash->{fpx} = 'on' if ($fpx =~ /^(t|true|yes|on|1)$/oi);
	$objHash->{fpx} = 'off' if ($fpx =~ /^(f|false|no|off|0)?$/oi);

	my $vac = vacation_get_on($userName);
	if($vac =~ /^(t|true|yes|on|1)$/oi) {
		$objHash->{vacation} = 'on';
	} else { $objHash->{vacation} = 'off'; }
	$objHash->{vacationmsg} = vacation_get_message($userName);

	my @alii = mail_virtuser_get_byuser($userName);
	my @alii_list;
	for (@alii) {
		if(/^\@/) {	
			push(@alii_list,$_);
		} else {	
			my $al = (split(/\@/, $_))[0];
			push(@alii_list,$al) unless($al eq $userName);
		}
	}
	@{ $objHash->{aliases}->{alias} } = @alii_list if(scalar(@alii_list) != 0);

	my @siteadms = vsite_admin_list($groupName);
	foreach my $admin (@siteadms) {
		$objHash->{admin} = 'on' if ($admin eq $userName);
	}
	unless($objHash->{admin}) {
		$objHash->{admin} = 'off';
	}
	return $objHash;
}


######################
# mailListRetrieve() #
# arguments:    list name, group name of vsite, fqdn of vsite
# returns:  data structure of mailing list info 
#
sub mailListRetrieve
{
	require Cobalt::List;

	my $list = shift;
	my $groupName = shift;
	my $siteName = shift || return;
	my $objHash = {};

	$objHash->{name} = $list;
	my $list = $list."_".$groupName;
    my @members = Cobalt::List::list_get_mem($list);
	my (@intlist,@extlist);
	foreach my $mem (@members) {
		if($mem =~ /^4475 Cannots/) {
			warn "ERROR: cannot get mailing list memebers for: ", $list, "\n";
			return;
		}
		if ($mem =~ /\@/) {
			push @extlist, $mem;
		} else {
			push @intlist, $mem;
		}
	}
	@{ $objHash->{intRecips}->{recip} } = @intlist if(scalar(@intlist) != 0);
	@{ $objHash->{extRecips}->{recip} } = @extlist if(scalar(@extlist) != 0);
	return $objHash;
}

###############
# vsiteBackup #
# syntax:       %conf = RaQ2::vsiteBackup($groupName);
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
		return;
	}

	open(FH, "< $cronFile") ||
		warn "RaQ2vsite::vsBackup Open failed: $cronFile: $!";
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
