#!/usr/bin/perl
# $Id: RaQXTRscanin.pl 931 2004-04-13 22:28:12Z  $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'scanin');
$cfg->parseOpts();

require RaQXTR;
require RaQUtil;
use TreeXml;
require Archive;
use MIME::Base64;

# Cobalt Perl Modules
require Cobalt::Util;

if(!$cfg->isDestDir) {
	my $dir = `pwd`; chomp($dir);
	$cfg->putDestDir($dir);
}
if(!-f $cfg->glb('inFile')) { die "$0: You must pass a valid file name\n" }

my $tree = readXml($cfg->glb('inFile'), 0);
if($cfg->isGlb('readConfig')) {
	if($cfg->isGlb('subsetNames')) { $tree = $cfg->removeNamesVsite($tree) }
	if($cfg->isIpaddr) { $tree = $cfg->convertIpaddr($tree) }
}
unless(defined $tree->{vsite}) {
	warn "ERROR: No virtual sites to import\n";
	exit 1;
}

# setup the default archive objects
my $varch = Archive->new(type => 'groups', destDir => $cfg->destDir, 
	sessID => $cfg->sess
);
my $uarch = Archive->new(type => 'users', destDir => $cfg->destDir,
	sessID => $cfg->sess
);

# Lock the UI
Cobalt::Util::lock_ui(1);

my ($vTree, $arch, @keys);
my @vsiteNames = RaQUtil::orderVsites($tree);
# we start with the vsites baby....
foreach my $fqdn (@vsiteNames) {
	warn "INFO: importing vsite: $fqdn\n";
	$vTree = $tree->{vsite}->{$fqdn};
	
	my $newGroup;
	if(!defined($vTree->{merge})) {
		$newGroup = RaQXTR::vsiteAdd($vTree);
		# set the fpx passowrd if nesscary.
		if($vTree->{fpx} eq 't' && $cfg->noPasswd eq 'f' && 
			defined($vTree->{fpxPassword})
		) { RaQUtil::setFpxPass("/home/sites/".$fqdn, $vTree->{fpxPassword}) }
	} else { $newGroup = RaQXTR::vsiteGroup($fqdn) }

	# if it wasn't created properly, bail
	if($newGroup) { 
		warn "Virtual site $fqdn created as $newGroup\n";
		$tree->{vsite}->{$fqdn}->{newGroup} = $newGroup;
	} else { 
		warn "ERROR: Vsite $fqdn was not created properly\n";
		delete $tree->{vsite}->{$fqdn};
		next;
	}
	# restore the backup if one exists
	my $ret;
	if(defined($vTree->{backup})) {
		$ret = RaQXTR::vsiteBackupAdd($vTree->{backup}, $newGroup);	
		if($ret == 1) { warn "scheduled backup added for $fqdn\n" }
		else { warn "could not create Scheduled backup for $fqdn\n" }
	}
} # end of vsite
	
my ($ok, $uTree);
# add the users for the vsite
if(defined $tree->{user}) {
@keys = keys %{ $tree->{user} };
foreach my $user (@keys) {
	next if($user eq 'admin');
	warn "INFO: importing user $user\n";
	$uTree = $tree->{user}->{$user};
	$uTree->{name} = $user;

	$uTree->{fullname} = decode_base64($uTree->{fullname})
		if(defined $uTree->{fullname});
	$uTree->{altname} = decode_base64($uTree->{altname})
		if(defined $uTree->{altname});
	$uTree->{vacationmsg} = decode_base64($uTree->{vacationmsg})
		if(defined $uTree->{vacationmsg});

	my $group = RaQXTR::vsiteGroup($uTree->{fqdn});
	if(!$group) {
		warn "ERROR: Could not find fqdn ", $uTree->{fqdn}, " for user $user\n";
		delete $tree->{user}->{$user};
		next;
	}

	if(!defined $uTree->{merge}) {
		$ok = RaQXTR::userAdd($uTree, $group);
		warn "User $user OK=$ok \n"; 
		if($ok eq 0) {
			warn "ERROR: Could not create user: $user\n";
			delete $tree->{user}->{$user};
			next;
		}
	} else { warn "merging user files: $user\n" }

	# do all the passwords at the end.
	# handle the file stuff
	if($cfg->confOnly eq 'f') {
		warn "INFO: restoring archive for $user\n";
		if(defined $uTree->{archives}) { 
			$uarch->setName($user);
			$uarch->setArchive($uTree->{archives});
			$uarch->setGid($group);
			$uarch->extractTar();
		} else { warn "INFO: no archives defined for $user\n" }
	}
}
if($cfg->noPasswd eq 'f') { RaQUtil::setShadowPass($tree->{user}); }
} # end of user add

# do the group files now that users are added
if($cfg->confOnly eq 'f') {
	@keys = keys %{ $tree->{vsite} };
	foreach my $fqdn (@keys) {
		warn "INFO: restoring archive for $fqdn\n";
		if(defined $tree->{vsite}->{$fqdn}->{archives}) { 
			$varch->setName($fqdn);
			$varch->setArchive($tree->{vsite}->{$fqdn}->{archives});
			$varch->setGid($tree->{vsite}->{$fqdn}->{newGroup});
			$varch->extractTar();
		} else { warn "INFO: no archives defined for $fqdn\n" }
	}
}

# Import the mailing lists
my $mTree;
if(defined $tree->{list}) {
foreach my $list (keys %{ $tree->{list} }) {
	warn "INFO: importing mailing list $list\n"; 
	$mTree = $tree->{list}->{$list};
	my $group = RaQXTR::vsiteGroup($mTree->{fqdn});
	unless($group) {
		warn "ERROR: Could not get site group for mailing list $list\n";
		next;
	}
	$ok = RaQXTR::mailListAdd($group, $mTree->{fqdn}, $mTree);
   	warn "creating mailing list $mTree->{name} OK=$ok\n";
} # end of mail list
}

# move admin's files back
if($cfg->confOnly eq 'f' && $cfg->adminFiles eq 't') {
	warn "INFO: restoring archive for user admin\n";
	if(defined $tree->{user}->{admin}->{archives}) { 
		$uarch->setName("admin");
		$uarch->setArchive($tree->{user}->{admin}->{archives});
		$uarch->setGid("admin");
		$uarch->extractTar();
	} else { warn "INFO: no archives defined for admin\n" }
}

if($cfg->dns eq 't') {
	warn "INFO: Importing DNS server settings\n";
	if(-f $cfg->destDir.'/records') {
		RaQUtil::importDNS($cfg->destDir.'/records');
	} else {
		warn "ERROR: DNS records files was not found\n";
	}
}

# unlock the UI
Cobalt::Util::lock_ui(0);

warn "INFO: We imported", TreeXml::getStats($tree);
RaQUtil::suspendAll($tree);
RaQUtil::hupAll('RaQXTR');

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
