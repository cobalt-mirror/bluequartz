# $Id: RaQUtil.pm 930 2004-04-13 22:28:12Z anders $ 
# Copyright (c) 2000 Cobalt Networks, Inc.
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
# Written By: Jeff Bilicki

package RaQUtil;

sub orderVsites
# this is intened to line up the site group names if possible
# Arguments: the vsite data stucture
# Return: an array with the order to create the vsites
{
	my $vTree = shift;

	my $maxVsites = 2000;
	my @vOrder = ();
	my @vLast = ();
	# get the currently used site groups
	setgrent();
	my %curGroups;
	while (my $group = getgrent) {
		next if($group !~ /^site\d+/);
    	$curGroups{$group} = 1;
	}

	my %siteHash;
	my $cnt = 0;
	# create a hash of fqdn => groupname also get a count of vsites
	foreach my $vsite (keys %{ $vTree->{vsite} }) {
		my $vsiteTree = $vTree->{vsite}->{$vsite};
		if(!$vsiteTree->{name}) { push(@vLast, $vsite); } 
		elsif($vsiteTree->{name} eq 'home') { push(@vLast, $vsite); } 
		else { $siteHash{ $vsiteTree->{name} } = $vsite; }
		$cnt++;
	}
	for(my $i = 1; $i < $maxVsites; $i++) {
		my $name = "site".$i;
		if(exists($curGroups{$name})) { push(@vLast,$siteHash{$name}); } 
		elsif(defined($siteHash{$name})) { push(@vOrder,$siteHash{$name}); }	
		else { push(@vOrder, pop(@vLast)) if(scalar(@vLast)); }
	}

	#warn "vOrder is: ", @vOrder, "\n";
	#warn "vLast is: ", @vLast, "\n";
	foreach my $site (@vLast) { push(@vOrder, $site); }

	# sanity check, keeping the same group isn't worth losing a vsite
	if(scalar(@vOrder) ne $cnt) {
		warn "WARN: orderVsites lost a vsite going with random order\n";
		@vOrder = ();
		foreach my $vsite (keys %{ $vTree->{vsite} }) {
			push(@vOrder, $vsite);
		}
	}
	return(@vOrder);
}

sub hupAll
# sytanx: 	$ret = hupAll($build);
# argument build name
# restart the sendmail and web servers
{
	my $build = shift; 

	require Cobalt::Http;
	require Cobalt::Email;

	Cobalt::Http::http_restart();
	Cobalt::Email::mail_sendmail_hup();

	if($build eq "RaQXTR") {
		# Turbo UI does play well cmu and shell tools
		system("/etc/rc.d/init.d/admserv restart");
	}
	return 1;
}

sub getFpxPass
# syntax:	$password = getFpxPass($siteDir);
# arugment: full path to the site dir
# returns:  webmaster encryted password
{
	my $dir = shift || return;
	my $passFile = $dir."/web/_vti_pvt/service.pwd";
	if(!-f $passFile) {
		warn "Cannot find password file: $passFile\n";
		return;
	}
	open(FH, "<$passFile") || die "Cannot open: $passFile:$!\n";

	my $passwd;
	while(my $line = <FH>) {
		$passwd = $2 if($line =~ /(webmaster:)(\S+)$/); 
	}
	close(FH);
	return $passwd;
}

sub setFpxPass
# syntax:	$ret = setFpxPass($siteDir, $passwd);
# arguments: full path to the site dir, encrypted password
# returns: sucess or failure
# side effects: edit the service.pwd file
{
	my $dir = shift || return;
	my $passwd = shift || return;
	my $passFile = $dir."/web/_vti_pvt/service.pwd";

	if(!-f $passFile) {
		warn "Cannot find password file: $passFile\n";
		return 0;
	}
	my $tmpFile = "/etc/locks/.service.pwd";
	open(FH, "<$passFile") || die "Cannot open: $passFile:$!\n";
	open(FH2, ">$tmpFile") || die "Cannot open: $tmpFile:$!\n";
	while(my $line = <FH>) {
		if($line =~ /(webmaster:)(\S+)$/) {
			print FH2 $1, $passwd, "\n";
		} else { print FH2 $line; }
	}
	close(FH);
	close(FH2);	
	my $ret = qx/cp $tmpFile $passFile/;
	unlink($tmpFile);

	return $ret;
}

sub setShadowPass
# argument: User Tree
# returns: success or failure
# side effects: actaully mods /etc/shadow
{
	my $uTree = shift || return 1;
	
	my $pwFile = "/etc/shadow";
	my $pwTmp = "/etc/locks/ptmp";

	my %uHash;
	foreach my $user (keys %{ $uTree }) {
		if(defined($uTree->{$user}->{md5_password})) {
			$uHash{$user} = $uTree->{$user}->{md5_password};
		} elsif(defined($uTree->{$user}->{crypt_password})) {
			$uHash{$user} = $uTree->{$user}->{crypt_password};
		}
	}

	open(SH, "< $pwFile") || die "cannnot open $pwFile\n";
	open(TMP, "> $pwTmp") || die "cannot open $pwTmp\n";

	while (my $line = <SH>) {
		chomp($line);
		my @data = split(":",$line);
		if(defined($uHash{$data[0]})) {
			print TMP $data[0], ":", $uHash{$data[0]}, ":",
				$data[2], ":", $data[3], ":", $data[4], ":",
				$data[5], ":", $data[6], ":", $data[7], ":", "\n";
		} else { print TMP $line, "\n" }
	}
	close(SH);
	close(TMP);
	rename($pwTmp, $pwFile);
	chmod 0400, $pwFile;
}

sub suspendAll
# syntax: RaQUtil::suspendAll($tree)
# Goes through a migrate object and suspends all the correct
# vsites and users
{
	require Cobalt::Vsite;
	require Cobalt::User;
	require Cobalt::Meta;

	my $tree = shift || return 1;
	my @suspend;

	if(defined $tree->{vsite}) {
	my ($group, $vobj);
	foreach my $fqdn (keys %{ $tree->{vsite} }) {
		$group = $tree->{vsite}->{$fqdn}->{newGroup};
		setgrent;
		next unless(getgrnam($group));
		if($tree->{vsite}->{$fqdn}->{suspend} =~ /(t|true|yes|on|1)/oi) {
			$vobj = Cobalt::Meta->create(type => "vsite");
			$vobj->retrieve($group);
			Cobalt::Vsite::vsite_disable($group);
			$vobj->put(suspend => 'on');
			$vobj->save;
		}
	}
	}
	if(defined $tree->{user}) {
	my $uobj;
	foreach my $user (keys %{ $tree->{user} }) {
		setpwent;
		next unless((getpwnam($user))[0]);
		if($tree->{user}->{$user}->{suspend} =~ /(t|true|yes|on|1)/oi) {
			$uobj = Cobalt::Meta->create(type => "users");
			$uobj->retrieve($tree->{user}->{$user}->{name});
			push @suspend, $tree->{user}->{$user}->{name};
			$uobj->put(suspend => 'on');
			$uobj->save;
		}
	}
	Cobalt::User::user_disable(@suspend) if(scalar @suspend);	
	}
	return 0;
}

sub getVacation
# argument: user name
# returns vacation message
{
	my $user = shift || return 0;

	my $vacaDir = "/var/vacation";
	my $msgFile = $vacaDir."/".$user.".msg";
	my $msg;

	if(-f $msgFile) {
		$msg = `cat $msgFile`;
	} else { return }
	return $msg;
}

sub importDNS
{
	my $records_file = shift;
	my $dest = '/etc/named/records';
	my $cgi_cmd = '/usr/admserv/cgi-bin/.cobalt/dns/index.cgi < /dev/null > /dev/null';
	my $cmd;

	if(! -f $records_file) { return 0; }
	if(-f $dest) {
		$cmd = 'cp '.$dest.' '.$dest.'.cmuimport'.$$;
		system($cmd);
	}
	$cmd = 'cp '.$records_file.' '.$dest;
	system($cmd);
	system($cgi_cmd);

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
