#!/usr/bin/perl
# $Id: RaQXTRadjust.pl 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http::/www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'adjust');
$cfg->parseOpts();

require TreeXml;

my ($cmuXml, $outFile);
if($cfg->isSess) {
	$cmuXml = $cfg->cmuXml;
	$outFile = $cfg->sessXml;
	if(-f $outFile) { $cmuXml = $outFile; }
} else { die "ERROR: no session id given, cannot adjust files\n" }

my $tree = TreeXml::readXml($cmuXml, 0);

$tree->{adjustPlatform} = "RaQXTR";

if(defined $tree->{vsite}) {
my $vTree;
foreach my $vsite (keys %{ $tree->{vsite} }) {
	$vTree = $tree->{vsite}->{$vsite};
	# these din't exist b4 raq 4
	$vTree->{dns_auto} = 'f';
	if(defined $vTree->{backup}) {
		my $bTree = {};
		$bTree->{backupCgi} = "/usr/local/sbin/site_backup.pl";
		$bTree->{cronTime} = "0 0 * * *";
		$bTree->{PASSWORD} = $vTree->{backup}->{password};
		$bTree->{PROTOCOL} = uc($vTree->{backup}->{protocol});
		$bTree->{frequency} = $vTree->{backup}->{scheduled};
		my ($user, $res) = split("\@", $vTree->{backup}->{resource});
		$bTree->{USER} = $user;
		$bTree->{REMOTE_USER} = $user;
		if($bTree->{PROTOCOL} eq 'FTP') {
			my ($resource, $share) = split(":", $res);
			$bTree->{HOST} = $resource;
			$bTree->{ftp_username} = $user;
			if($share) { 
				$bTree->{REMOTE_PATH} = $share;
				$bTree->{ftp_share}.$share 
			} else { $bTree->{ftp_share} = $resource."/" }
		} elsif($bTree->{PROTOCOL} eq 'SMB') {
			$bTree->{smb_username} = $user;
			$bTree->{smb_share} = $res;
			if($res =~ /^\\\\(\S+)\\(.*)/) {
				$bTree->{HOST} = $1;
				$bTree->{REMOTE_PATH} = $2;
			}
		}	
		delete $vTree->{backup};
		$vTree->{backup} = $bTree;
	}
}
}

if(defined $tree->{user}) {
my $uTree;
require Jcode;
require MIME::Base64;
import MIME::Base64 qw(decode_base64 encode_base64);
my ($j, $name);
foreach my $user (keys %{ $tree->{user} }) {
	$uTree = $tree->{user}->{$user};
	if(defined $uTree->{fullname}) {
		$name = decode_base64($uTree->{fullname});
		if(Jcode::getcode($name) eq 'sjis') {
			$j = Jcode->new($name);
			$uTree->{fullname} = encode_base64($j->euc, '');
		}
	}
	if(defined $uTree->{altname}) {
		$name = decode_base64($uTree->{altname});
		if(Jcode::getcode($name) eq 'sjis') {
			$j = Jcode->new($name);
			$uTree->{altname} = encode_base64($j->euc, '');
		}
	}
	if($uTree->{vacation} eq 't') {
		$name = decode_base64($uTree->{vacationmsg});
		if(Jcode::getcode($name) eq 'sjis') {
			$j = Jcode->new($name);
			$uTree->{vacationmsg} = encode_base64($j->euc, '');
		}
	}

}
}


my $migrate = {};
TreeXml::addNode('migrate', $tree, $migrate);
TreeXml::writeXml($migrate, $outFile);

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
