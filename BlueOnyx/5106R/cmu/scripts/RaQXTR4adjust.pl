#!/usr/bin/perl
# $Id: RaQXTR4adjust.pl 922 2003-07-17 15:22:40Z will $
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

$tree->{adjustPlatform} = "RaQ4";

if(defined $tree->{vsite}) {
my $vTree;
foreach my $vsite (keys %{ $tree->{vsite} }) {
	$vTree = $tree->{vsite}->{$vsite};
	delete $vTree->{dns_auto} if(defined($vTree->{dns_auto}));
	if(defined($vTree->{backup})) {
		my $bTree = {};
		$bTree->{name} = 'backup';
		$bTree->{backupset} = 'site';
		$bTree->{password} = $vTree->{backup}->{PASSWORD};
		$bTree->{protocol} = lc($vTree->{backup}->{PROTOCOL});
		$bTree->{scheduled} = $vTree->{backup}->{frequency};
		if($bTree->{protocol} eq 'ftp') {
			$bTree->{resource} = $vTree->{backup}->{ftp_username}.
				"\@".$vTree->{backup}->{HOST};
		} elsif($bTree->{protocol} eq 'smb') {
			$bTree->{resource} = $vTree->{backup}->{smb_username}.
				"\@".$vTree->{backup}->{smb_share};
		} elsif($bTree->{protocol} eq 'nfs') {
			$bTree->{resource} = $vTree->{backup}->{nfs_share};
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
		if(Jcode::getcode($name) eq 'euc') {
			$j = Jcode->new($name);
			$uTree->{fullname} = encode_base64($j->sjis, '');
		}
	}
	if(defined $uTree->{altname}) {
		$name = decode_base64($uTree->{altname});
		if(Jcode::getcode($name) eq 'euc') {
			$j = Jcode->new($name);
			$uTree->{altname} = encode_base64($j->sjis, '');
		}
	}
	if($uTree->{vacation} eq 't') {
		$name = decode_base64($uTree->{vacationmsg});
		if(Jcode::getcode($name) eq 'euc') {
			$j = Jcode->new($name);
			$uTree->{vacationmsg} = encode_base64($j->sjis, '');
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
