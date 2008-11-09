#!/usr/bin/perl
# $Id: RaQ4scanout.pl 931 2004-04-13 22:28:12Z  $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'scanout');
$cfg->parseOpts();

require RaQ4;
require RaQUtil;
use TreeXml;
require Archive;
use MIME::Base64;

if(!-d $cfg->destDir) {
	warn "ERROR: destination directory does not exsist: ", $cfg->destDir, "\n";
	exit 1;
}
if(!$cfg->glb('outFile')) { $cfg->putGlb('outFile', $cfg->cmuXml) }

my @encodedFields = (
	'fullname', 
	'altname',
	'vacationmsg',
); 

# data structure to build
my $tree;

$tree->{migrate}->{exportPlatform} = 'RaQ4';
$tree->{migrate}->{adjustPlatform} = 'RaQ4';
$tree->{migrate}->{cmuVersion} = $VERSION;
$tree->{migrate}->{cmuDate} = time();

my $baseFqdn = `/bin/uname -n`;
chomp($baseFqdn);
$tree->{migrate}->{baseHost} = $baseFqdn;

# create the default archive objects
my $varch = Archive->new(type => 'groups', destDir => $cfg->destDir);
my $uarch = Archive->new(type => 'users', destDir => $cfg->destDir);

my @vsites = RaQ4::migrateGetVsites();
foreach my $fqdn (@vsites) {
	if($cfg->isSubSet) { next unless($cfg->isSubSet($fqdn)) }

	my $group = RaQ4::vsiteGroup($fqdn);
	my $vobj = RaQ4::vsiteRetrieve($group);
	if($vobj) { warn "INFO: exporting vsite $fqdn\n" }
	else { next }

	# remap the ipaddr if needed
	if($cfg->isIpaddr) { $vobj->{ipaddr} = $cfg->ipaddr }

	foreach my $encField (@encodedFields) {
		$vobj->{$encField} = encode_base64($vobj->{$encField}, '') 
			if(defined($vobj->{$encField}));
	}

	if($vobj->{fpx} eq 't' && $cfg->noPasswd eq 'f') {
		$vobj->{fpxPassword} = RaQUtil::getFpxPass("/home/sites/".$fqdn);
	}

	# get the backup
	my $backup = RaQ4::vsiteBackup($group);
	if($backup) { $vobj->{backup} = $backup }

	if($cfg->confOnly eq 'f') {
		warn "INFO: building archive for $fqdn\n";
		$varch->setName($fqdn);
		$varch->buildTar();
		$vobj->{archives} = $varch->archives;
	}

	$vobj->{id} = $fqdn;
	push @{ $tree->{migrate}->{vsite} }, $vobj;

	# get the vsite users
	my @users = RaQ4::vsiteUserChildren($group);
	foreach my $user (@users) {
		my $uobj = RaQ4::userRetrieve($user);
		if($uobj) { warn "INFO: exporting user $user\n" }
 		else { next }
	
		foreach my $encField (@encodedFields) {
			$uobj->{$encField} = encode_base64($uobj->{$encField}, '') 
				if(defined($uobj->{$encField}));
		} 

		if($cfg->confOnly eq 'f') {
			warn "INFO: building archive for $user\n";
			$uarch->setName($user);
			$uarch->buildTar();
			$uobj->{archives} = $uarch->archives;
		}

		$uobj->{fqdn} = $fqdn;
		$uobj->{id} = $user;
		push @{ $tree->{migrate}->{user} }, $uobj;
	} # end of user

	# get the vsite mailing lists
	my @mailLists = RaQ4::vsiteMLChildren($group);
	foreach my $list (@mailLists) {
		my $mobj = RaQ4::mailListRetrieve($list,$group,$fqdn);
		if($mobj) { warn "INFO: exporting mailing list $list\n" }
		else { next }

		$mobj->{group} = $group;
		$mobj->{fqdn} = $fqdn;
		$mobj->{name} = $list;
		$mobj->{id} = $fqdn."-".$list;
		push @{ $tree->{migrate}->{list} }, $mobj;
	} # end of mailList
} # end of vsite

if($cfg->confOnly eq 'f' && $cfg->adminFiles eq 't') {
	my $adm = {}; $adm->{id} = 'admin'; $adm->{fqdn} = $baseFqdn;
	$adm->{merge} = 't';
	warn "INFO: building archive for user admin\n";
	
	$uarch->setName('admin');
	$uarch->buildTar();
	$adm->{archives} = $uarch->archives;
	push @{ $tree->{migrate}->{user} }, $adm;
}

my %uHash;
my $pwFile = "/etc/shadow";
# Get the passwords from /etc/shadow
if($cfg->noPasswd eq 'f' && defined $tree->{migrate}->{user}) {
	open(SHADOW, "< $pwFile") || warn "$0: Open failed: $pwFile: $!\n";
	while (my $line = <SHADOW>) {
		my ($user,$passwd) = (split(":",$line))[0,1];
		next if($user eq 'root');
		$uHash{$user} = $passwd;
	}
	close(SHADOW);

	foreach my $user (@{ $tree->{migrate}->{user} }) {
		if(defined $uHash{ $user->{id} } && $user->{id} ne 'admin') {
			$user->{md5_password} = $uHash{ $user->{id} };
		}
	}
}

my $cmd;
if($cfg->dns eq 't') {
	warn "INFO: Exporting DNS records\n";
	$cmd = 'cp /etc/named/records '.$cfg->destDir;
	system($cmd);	
}

warn "INFO: We exported", TreeXml::getStats($tree->{migrate});
if($cfg->isGlb('outFile')) {
    TreeXml::writeXml($tree, $cfg->glb('outFile'));
} else {
	# this is needed for open3
	close(STDERR);
    print TreeXml::writeXml($tree);
}

exit 0;
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
