#!/usr/bin/perl
# $Id: Qube3scanin.pl 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/sausalito/perl";
use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'scanin');
$cfg->parseOpts();


use cmuCCE;
use I18n;
use TreeXml;
require Archive;

if(!$cfg->isDestDir) {
	my $dir = `pwd`; chomp($dir);
	$cfg->putDestDir($dir);
}
if(!-f $cfg->glb('inFile')) { die "$0: You must pass a valid file name\n" }

my $tree = readXml($cfg->glb('inFile'), 0);

my %encodeAttr = (
	fullName => 1,
	sortName => 1,
	description => 1,
	vacationMsg => 1
);

my %arrayVal = (
	members => 'member',
	aliases	=> 'alias',
	mailAliases => 'mailAlias',
	webAliases => 'webAlias',
	forwardEmail =>	'forward',
	remote_recips => 'recip',
	local_recips => 'recip',
	capabilities => 'cap',
	capLevels => 'cap',
);

my %classes = (
	System		=>	[qw(serialNumber SWUpdate productSerialNumber RAID)],
	Network		=>	[qw(mac)],
);


my $cce = new cmuCCE;
$cce->connectuds();
$cce->auth('admin');

my $i18n = new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale($cce));

%{ $cce->{_arrayVal} } = %arrayVal;
%{ $cce->{_encodeAttr} } = %encodeAttr;
%{ $cce->{_classes} } = %classes;

# setup the default archive objects
my $garch = Archive->new(type => 'groups', destDir => $cfg->destDir, 
	sessID => $cfg->sess
);
my $uarch = Archive->new(type => 'users', destDir => $cfg->destDir,
	sessID => $cfg->sess
);


my ($ok, $bad, @info, $oid, $arch);

# first we add users
if(defined $tree->{user}) {
foreach my $user (keys ( %{$tree->{user}} )) {
	next if($user eq 'admin');
	my $uTree = $tree->{user}->{$user};

	my $uRef = $cce->unLoadHash($uTree);
    $uTree->{name} = $uRef->{name} = $user;
	warn "INFO: importing user $user\n";
    
	delete $uRef->{type};
	delete $uRef->{uid};
	delete $uRef->{smb_password};
	
	my $capLevels = $uRef->{capLevels};
	delete $uRef->{capLevels};
	my $caps = $uRef->{capabilities};
	delete $uRef->{capabilities};

    ($ok, $bad, @info) = $cce->create('User', $uRef);
	# skip if user creatation failed
 	if($ok == 0) {
		$cce->printReturn($ok, $bad, @info);
		next;
	} else { warn "User $uRef->{name} OK=$ok \n" }
    $oid = $cce->oid();
	next unless($oid);
	
	$cce->unLoadNamespace($uTree, $oid);

	# set the caps
	if($caps) {
		($ok, $bad, @info) = $cce->set($oid, '', { capabilities => $caps });
		if($ok == 0) { $cce->printReturn($ok, $bad, @info) } 
	}

	if($capLevels) {
		($ok, $bad, @info) = $cce->set($oid, '', { capLevels => $capLevels });
		if($ok == 0) { $cce->printReturn($ok, $bad, @info) } 
	}

	# slap the crypt and md5 if it is around
	if($cfg->noPasswd eq 't') {
		($ok, $bad, @info) = $cce->set($oid, '', 
			{ password => $cfg->glb('userPasswd') });
		if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
		warn "Setting default password for $uTree->{name} OK=$ok\n";
	} else {
		if(defined($uTree->{crypt_password})) {
			($ok, $bad, @info) = $cce->set($oid, '', 
				{ crypt_password => $uTree->{crypt_password} });
			if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
			warn "User $uTree->{name} setting crypt passwd OK=$ok\n";
		}
		if(defined($uTree->{md5_password})) {
			($ok, $bad, @info) = $cce->set($oid, '', 
				{ md5_password => $uTree->{md5_password} });
			if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
			warn "User $uTree->{name} setting md5 passwd OK=$ok\n";
		} elsif(defined($uTree->{crypt_password})) {
			($ok, $bad, @info) = $cce->set($oid, '', 
				{ md5_password => $uTree->{crypt_password} });
			if($ok == 0) { $cce->printReturn($ok, $bad, @info) }
			warn "User $uTree->{name} setting md5 passwd with crypt passwd OK=$ok\n";
		} 
	}

	if($cfg->confOnly eq 'f') {
		warn "INFO: restoring archive for $user\n";
		if(defined $uTree->{archives}) { 
			$uarch->setName($user);
			$uarch->setArchive($uTree->{archives});
			$uarch->extractTar();
		} else { warn "INFO: no archives defined for $user\n" }
	}
}
} # end of users

if($cfg->noPasswd eq 'f') {
# super f-ugly
# /etc/smbpasswd
my $Ptmp = "/etc/locks/pstmp";
my $pwFile;
if(-f "/etc/samba/smbpasswd") { $pwFile = "/etc/samba/smbpasswd" }
else { $pwFile = "/etc/smbpasswd" }
open(SHADOW, "< $pwFile") || die "$0: Open failed: $pwFile: $!\n";
open(STMP, "> $Ptmp") || die "$0: Open failed: $Ptmp: $!\n";
while (my $line = <SHADOW>) {
	my ($curUser,$uid,$name,$dir) = (split(":",$line))[0,1,4,5];
	next unless($curUser);
	if(defined($tree->{user}->{$curUser}->{smb_password})) {
		print STMP "$curUser:$uid:", 
			$tree->{user}->{$curUser}->{smb_password}, 
			":$name:$dir:\n";
	} else { print STMP $line; }       
}

close(SHADOW);
close(STMP);
qx/mv $Ptmp $pwFile/;
}

# move admin's files back
if($cfg->confOnly eq 'f' && $cfg->adminFiles eq 't') {
	warn "INFO: restoring files for user admin\n";
	if(defined $tree->{user}->{admin}->{archives}) { 
		$uarch->setName("admin");
		$uarch->setArchive($tree->{user}->{admin}->{archives});
		$uarch->extractTar();
	} else { warn "INFO: no archives defined for admin\n" }
}

if(defined $tree->{group}) {
foreach my $group (keys %{ $tree->{group} }) {
	my $gTree = $tree->{group}->{$group};
	warn "INFO: importing group $group\n";

	my $gRef = $cce->unLoadHash($gTree);
	if(!defined($gRef->{merge})) {
    	$gRef->{name} = $group;
		($ok, $bad, @info) = $cce->create('Workgroup', $gRef);
		if($ok == 0) {
			$cce->printReturn($ok, $bad, @info);
			next;
		} else { warn "Group $gRef->{name} OK=$ok \n" }
		$oid = $cce->oid();
	} else { 
		# merge the group
		($oid) = $cce->find("Workgroup", {'name' => $group });
		unless($oid) {
			warn "ERROR: Could not merge group $group\n";
			next;
		}
		delete $gRef->{merge};
		if(keys %{ $gRef }) {
			if(defined($gRef->{members})) {
				my $obj;
				($ok, $obj) = $cce->get($oid);
				if(defined($obj->{members})) {
					my @mems = $cce->scalar_to_array($obj->{members});
					foreach my $mem (@mems) { $gRef->{members} .= $mem.'&'; }
				}
			}
			($ok, $bad, @info) = $cce->set($oid, '', $gRef);
			if($ok == 0) {
				$cce->printReturn($ok, $bad, @info);
				warn "ERROR: Could not merge group attributes: $group\n";
				next;		
			}
		}
	}
	$cce->unLoadNamespace($gTree, $oid);

	if($cfg->confOnly eq 'f') {
		warn "INFO: restoring archive for $group\n";
		if(defined $tree->{group}->{$group}->{archives}) { 
			$garch->setName($group);
			$garch->setArchive($tree->{group}->{$group}->{archives});
			$garch->extractTar();
		} else { warn "INFO: no archives defined for $group\n" }
	}
}
} # end of group add

if(defined $tree->{list}) {
foreach my $list (keys %{ $tree->{list} }) {
	my $mTree = $tree->{list}->{$list};

	my $mRef = $cce->unLoadHash($mTree);
    $mTree->{name} = $mRef->{name} = $list;
	warn "INFO: importing mailing list $list\n"; 

	if(defined($mTree->{group})) {
		$mRef->{local_recips} = rmGroupMems($cce, $mRef) 
			if (defined $mRef->{local_recips});
		($oid) = $cce->find("MailList", {'name' => $mRef->{name} });
		delete $mRef->{name};
		delete $mRef->{group};
		delete $mRef->{empty} if(defined $mRef->{empty});
		# nothing to import
		next if(!defined(keys %{ $mRef } || !$oid));
		($ok, $bad, @info) = $cce->set($oid, '', $mRef);
		if($ok == 0) {
			$cce->printReturn($ok, $bad, @info);
			next;		
		}
	} else {
		delete $mRef->{empty} if(defined $mRef->{empty});
		($ok, $bad, @info) = $cce->create('MailList', $mRef);
		if($ok == 0) {
			$cce->printReturn($ok, $bad, @info);
			next;
		} else { warn "list $mRef->{name} OK=$ok \n" }
		$oid = $cce->oid();
	}
	$cce->unLoadNamespace($mTree, $oid);
} # end of mailLists
}

$cce->bye("bye");

warn "INFO: We imported", TreeXml::getStats($tree);
exit 0;

sub rmGroupMems
{
	my $cce = shift;
	my $ref = shift;

	my ($ok, $obj, @diff);
	my ($id) = $cce->find("Workgroup", {'name' => $ref->{name} });
	return unless($id);
	($ok, $obj) = $cce->get($id);
	return unless($ok);
	my %seen = map { $_, 1 } $cce->scalar_to_array($obj->{members});
	foreach my $k ($cce->scalar_to_array($ref->{local_recips})) {
		push(@diff, $k) unless(exists($seen{$k}));
	}
	return ($cce->array_to_scalar(@diff));
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
