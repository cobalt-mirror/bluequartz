#!/usr/bin/perl
# $Id: Qube2scanout.pl 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'scanout');
$cfg->parseOpts();


require Qube2;
use TreeXml;
require Archive;
use MIME::Base64;

if(!-d $cfg->destDir) {
	warn "ERROR: destination directory does not exsist: ", $cfg->destDir, "\n";
	exit 1;
}
if(!$cfg->glb('outFile')) { $cfg->putGlb('outFile', $cfg->cmuXml) }


my @encodedFields = qw(fullName vacationMsg); 
my $tree; # data structure to build


$tree->{migrate}->{exportPlatform} = 'Qube2';
$tree->{migrate}->{adjustPlatform} = 'Qube2';
$tree->{migrate}->{cmuVersion} = $VERSION;
$tree->{migrate}->{cmuDate} = time();

my $baseFqdn = `/bin/uname -n`;
chomp($baseFqdn);
$tree->{migrate}->{baseHost} = $baseFqdn;

# create the default archive objects
my $garch = Archive->new(type => 'groups', destDir => $cfg->destDir);
my $uarch = Archive->new(type => 'users', destDir => $cfg->destDir);

# first we get users
my @users = Qube2::migrateGetUsers();
foreach my $user (@users) {
    my $uRef = Qube2::userRetrieve($user);
	warn "INFO: exporting user $user\n";
	$uRef->{id} = $user;

    foreach my $encField (@encodedFields) {
	 $uRef->{$encField} = encode_base64($uRef->{$encField}, '') 
		if(defined($uRef->{$encField}));
    }
   
	if($cfg->noPasswd eq 't') {
		delete $uRef->{crypt_password};
		delete $uRef->{smb_password};
	}

    # tar files if needed
	if($cfg->confOnly eq 'f') {
		warn "INFO: building archive for $user\n";
		$uarch->setName($user);
		$uarch->buildTar();
		$uRef->{archives} = $uarch->archives;
    }
	push @{ $tree->{migrate}->{user} }, $uRef;
  
}

# handle admin's files
if($cfg->confOnly eq 'f' && $cfg->adminFiles eq 't') {
	my $adm = {}; $adm->{id} = 'admin'; $adm->{merge} = 't';
	warn "INFO: building archive for user admin\n";
	
	$uarch->setName('admin');
	$uarch->buildTar();
	$adm->{archives} = $uarch->archives;
	push @{ $tree->{migrate}->{user} }, $adm;
}

my @groups = Qube2::migrateGetGroups();
foreach my $group (@groups) {
    my $gRef = Qube2::groupRetrieve($group);
	warn "INFO: exporting group $group\n";
	$gRef->{id} = $group;
    
	if($cfg->confOnly eq 'f') {
		warn "INFO: building archive for $group\n";
		$garch->setName($group);
		$garch->buildTar();
		$gRef->{archives} = $garch->archives;
	}

   	push @{ $tree->{migrate}->{group} }, $gRef; 
}

my @mailLists = Qube2::migrateGetMailLists();
foreach my $list (@mailLists) {
    my $mRef = Qube2::mailListRetrieve($list);
	warn "INFO: exporting mailing list $list\n";	
	$mRef->{id} = $list;

    if (!defined $mRef->{local_recips} && !defined $mRef->{remote_recips}) {
		$mRef->{empty} = "yes";
	}
	push @{ $tree->{migrate}->{list} }, $mRef;
}


warn "INFO: We exported", TreeXml::getStats($tree->{migrate});
if ($cfg->isGlb('outFile')) {
    TreeXml::writeXml($tree, $cfg->glb('outFile'));
} else {
	# this is needed for open3
	# do nothing since stdout is closed and we don't want to
	# see any bitching about it
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
