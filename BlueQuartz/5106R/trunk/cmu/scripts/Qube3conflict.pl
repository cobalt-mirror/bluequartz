#!/usr/bin/perl
# $Id: Qube3conflict.pl 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http::/www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'conflict');
$cfg->parseOpts();

use QubeConflict;
use TreeXml;

# parse the file stuff
my ($resoXml, $eTree, $iTree);
# resolution
if(!$cfg->isGlb('resoXml')){
	 die "you need to provide a filename for the final resolution\n";
}

# current Tree
if(-f $cfg->glb('exData')) { $eTree = TreeXml::readXml($cfg->glb('exData'), 0) }
else { die "you need to provide an export filename\n"; }

#imported Tree
if(-f $cfg->glb('imData')) { $iTree = TreeXml::readXml($cfg->glb('imData'), 0) } 
else { die "you need to provide an import filename\n"; }
my $flict = QubeConflict->new($eTree, $iTree, $cfg->{glbConf});

# get the session ID
if($cfg->isSess) { $flict->{sessID} = $cfg->sess; }
else { die "you must provide a session id\n"; }


# pull the aliases up to the top
TreeXml::addNode('aliases', $flict->raiseAlii($EX), $flict->{$EX});

# this stores good users, aliases, groups, and maillist names

# be uber paraniod about recursion
my (@keys, $ret);
my @queue = ();

# collisions for users
warn "\n===> Checking Users <===\n";
@keys = keys %{ $flict->{$IM}->{user} };	
foreach my $user (@keys) {
	$ret = $flict->detectUserName($user);	
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }

	$ret = $flict->detectUserNameNumber($user);	
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }

	$ret = $flict->detectUserNameLength($user);	
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }
}

if($cfg->webEnabled eq 'f') {
	runQueue($flict, @queue);
	@queue = ();
}

# do this after users in case something gets remapped
TreeXml::addNode('aliases', $flict->raiseAlii($IM), $flict->{$IM});

# handle that aliases BS
warn "===> Checking User Aliases <===\n";
if(defined $flict->{$IM}->{user}) {
@keys = keys %{ $flict->{$IM}->{user} };	
foreach my $user (@keys) {
	next unless(defined $flict->{$IM}->{user}->{$user}->{Email}->{aliases});
	my @uAlii = @{ $flict->{$IM}->{user}->{$user}->{Email}->{aliases}->{alias} };
	foreach my $al (@uAlii) {
		warn "checking aliases: ", $al, "\n";
		$ret = $flict->detectAliases($EX, $al);
		if(ref($ret) eq 'Resolve') { push(@queue, $ret) }
	} 
}
}

if($cfg->webEnabled eq 'f') {
	runQueue($flict, @queue);
	@queue = ();
}
# in case stuff was changed
TreeXml::deleteNode('aliases', $flict->{$IM});
TreeXml::addNode('aliases', $flict->raiseAlii($IM), $flict->{$IM});
    
# group collisions
warn "===> Checking Groups <===\n";
@keys = keys %{ $flict->getGroups($IM) };
foreach my $group (@keys) {
	$ret = $flict->detectGroupName($group);	
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }

	$ret = $flict->detectGroupNameNumber($group);	
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }
}

if($cfg->webEnabled eq 'f') {
	runQueue($flict, @queue);
	@queue = ();
}

# mail collisions
warn "===> Checking Mailing Lists <===\n";
if(defined $flict->{$IM}->{list}) {
@keys = keys %{ $flict->getMailLists($IM) };
foreach my $list (@keys) {
	next if(defined($flict->{$IM}->{list}->{$list}->{group}));
	$ret = $flict->detectMailList($list);	
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }

	$ret = $flict->detectMailListNumber($list);	
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }
}
}

if($cfg->webEnabled eq 'f') {
	runQueue($flict, @queue);
	@queue = ();
}


TreeXml::deleteNode('aliases', $flict->{$IM});
$flict->removeEmpty($IM);
my $final = {};
TreeXml::addNode('migrate', $flict->{$IM}, $final);
TreeXml::writeXml($final, $cfg->glb('resoXml'));

exit 0;

sub runQueue
{
	my $flict = shift;
	my @que = @_;
	my ($detector, $newVal);

	foreach my $reso (@queue) {
		$reso->getAction();
		if($reso->result() eq 'changeClass') {
			$detector = $reso->detector();
			$newVal = $reso->getInput();
			$ret = $flict->$detector($EX, $newVal);
			if($ret) { 
				warn "$newVal will cause a conflict\n"; 
				redo; 
			}
			$ret = $flict->$detector($IM, $newVal);
			if($ret) { 
				warn "$newVal will cause a conflict\n"; 
				redo;
			}
			$flict->runResult($reso);
		} else { $flict->runResult($reso) }
	}
	return $flict;
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
