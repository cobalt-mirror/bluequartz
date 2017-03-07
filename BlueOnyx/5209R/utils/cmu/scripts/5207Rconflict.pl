#!/usr/bin/perl
# $Id: 5207Rconflict.pl 943 2005-08-23 10:20:58Z shibuya $
# Cobalt Networks, Inc http::/www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'conflict');
$cfg->parseOpts();

use RaQCCEConflict;
use TreeXml;
	
# parse the file stuff
my ($eTree, $iTree);
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
my $flict = RaQCCEConflict->new($eTree, $iTree, $cfg->{glbConf});

# get the session ID
if($cfg->isSess) { $flict->{sessID} = $cfg->sess; }
else { die "you must provide a session id\n"; }

# be uber paraniod about recursion
my (@keys, $ret, $result, @queue);

warn "\n===> Checking Virtual Sites <===\n";
@keys = keys %{ $flict->getVsites($IM) };
foreach my $site (@keys) {
	#$ret = $flict->detectIpService($site, 'ssl');
	#if(ref($ret) eq 'Resolve') { push(@queue, $ret) }

	#$ret = $flict->detectIpService($site, 'ssl', $IM);
	#if(ref($ret) eq 'Resolve') { push(@queue, $ret) }
	
	$ret = $flict->detectIpService($site, 'ftp');
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }

	$ret = $flict->detectIpService($site, 'ftp', $IM);
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }

	$ret = $flict->detectWebDomains($site);
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }
	elsif(ref($ret) eq 'ARRAY') {
		foreach my $r (@{ $ret }) { push(@queue, $r) }
	}

	$ret = $flict->detectEmailDomains($site);
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }
	elsif(ref($ret) eq 'ARRAY') {
		foreach my $r (@{ $ret }) { push(@queue, $r) }
	}

	$ret = $flict->detectVsiteFqdn($site);	
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }
}

if($cfg->webEnabled eq 'f') {
	runQueue($flict, @queue);
	@queue = ();
}
warn "===> Checking Users <===\n";
@keys = keys %{ $flict->getUsers($IM) };	
foreach my $user (@keys) {
	$ret = $flict->detectUserName($user);
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }

	$ret = $flict->detectUserNameNumber($user);	
	if(ref($ret) eq 'Resolve') { push(@queue, $ret) }
}

if($cfg->webEnabled eq 'f') {
	runQueue($flict, @queue);
}

if($cfg->webEnabled eq 't' && scalar(@queue) > 0) {
	my $i = 0;
	my $conflict = {};
	foreach my $res (@queue) {
		$res->name("conflict.$i");
		my $tree = {};
		foreach my $key (keys %{ $res }) {
			$tree->{$key} = $res->{$key};
		}
		push @{ $conflict->{conflict} }, $tree;
		$i++;
	}
	my $cTree = {};
	TreeXml::addNode('conflicts', $conflict, $cTree);
	TreeXml::printXml($cTree);
} else {
	$flict->removeEmpty($IM);
	my $final = {};
	TreeXml::addNode('migrate', $flict->{$IM}, $final);
	TreeXml::writeXml($final, $cfg->glb('resoXml'));
}

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
			
			$ret = $flict->$detector($newVal);
			if(ref($ret) eq 'Resolve') { 
				warn "$newVal will cause a conflict\n"; 
				redo; 
			}
			$ret = $flict->$detector($newVal, $IM);
			if(ref($ret) eq 'Resolve') { 
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
