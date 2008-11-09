#!/usr/bin/perl
# $Id: RaQ550adjust.pl 922 2003-07-17 15:22:40Z will $
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

$tree->{adjustPlatform} = "RaQ550";

use vars qw(%classData %nameSpaceData %remapData);

my %classData = (
	vsite => {
		mailAliases => 'emaildomain',
		webAliases => 'webdomain',
	},
	user => {
		fullName => 'fullname',
		capLevels => 'admin',
	},
	list => {
		local_recips => 'intRecips',
		remote_recips => 'extRecips',
		apassword => 'mlPasswd',
		subPolicy => 'subscription',
		postPolicy => 'restrict',
	},
);	

my %nameSpaceData = (
	vsite => { 
		Disk => { quota => 'quota' },
		UserDefaults => { 
			quota => 'user_quota', 
			userNameGenMode => 'user_namegen',
			userlist_sort => 'userlist_sort',
			userlist_range => 'userlist_range',
		#	shell => 'user_shell',
		},
		Shell => { enabled => 'shell' },
		Asp => { enabled => 'casp' },
		Java => { enabled => 'java' },
		CGI => { enabled => 'cgi' },
		SSL => { enabled => 'ssl' },
		SSI => { enabled => 'ssi' },
		PHP => { enabled => 'php' },
		APOP => { enabled => 'apop' },
		AnonFtp => {
			enabled => 'ftp',
			maxConnections => 'ftpusers',
			quota => 'ftpquota',
		},
		Frontpage => {
			enabled => 'fpx',
			passwordWebmaster => 'fpxPassword',
		},
	},
	user => { 
		Disk => { quota => 'quota' }, 
		Email => {
			aliases => 'aliases',
			forwardEmail => 'forward',
			vacationOn => 'vacation',
			vacationMsg => 'vacationmsg',
		},
		Shell => { enabled => 'shell', },
		Frontpage => { enabled => 'fpx', },
		APOP => { enabled => 'apop', },
	},
	list => {
		Archive => {
			keep_for_days => 'keep_for_days',
			archiver => 'archiver',
			domain => 'domain'
		}
	}
);

my %remapData = (
	vsite => {
		userNameGenMode => {
			0 => 'firstInitLast',
			1 => 'firstLastInit',
			2 => 'first',
			3 => 'last',
		},	
	},
	user => {
		capLevels => {
			t => { cap => ['siteAdmin'] },
			f => { cap => [] },
		},
	},
	list => {
		subPolicy => {
			1 => 'open',
		},
		postPolicy => {
			1 => 'members',
		},
	}
);

foreach my $class (keys %{ $tree }) {
	if(ref($tree->{$class}) eq 'HASH') {
		foreach my $obj (keys %{ $tree->{$class} }) {
			convertClass($tree->{$class}->{$obj}, $class);
			convertNamespace($tree->{$class}->{$obj}, $class);
		}
		dropFalse($tree->{$class});
		convertBoolean($tree->{$class});
	} 
}

if(defined $tree->{user}) {
my($uTree, $fqdn, @arr);
my @keys = keys %{ $tree->{user} };
foreach my $user (@keys) {
	$uTree = $tree->{user}->{$user};

    # dirty hack for forward email
    if(defined $uTree->{Email}->{forwardEmail}) {
        $uTree->{Email}->{forwardEnable} = 1;
    }
	# dirty hack for apop password
	if(defined $uTree->{APOP}->{enabled}) {
		#warn "password is: ", $cfg->glb('userPasswd'), "\n";
		$uTree->{APOP}->{apop_password} = $cfg->glb('userPasswd');
	}

	next unless(defined $uTree->{Email}->{aliases});
	$fqdn = $uTree->{fqdn};
	@arr = @{ $uTree->{Email}->{aliases}->{alias} };
	for(my $i = 0; $i < @arr; $i++) {
		if($uTree->{Email}->{aliases}->{alias}->[$i] =~ /^\@$fqdn$/) {
			splice(@{ $uTree->{Email}->{aliases}->{alias} }, $i, 1);
			$tree->{vsite}->{$fqdn}->{mailCatchAll} = $uTree->{name};
		}
	}
	if(scalar @{ $uTree->{Email}->{aliases}->{alias} } eq 0) {
		delete $uTree->{Email}->{aliases};
	}
}
}

if(defined $tree->{vsite}) {
my $vTree;
my @keys = keys %{ $tree->{vsite} };
foreach my $vsite (@keys) {
	$vTree = $tree->{vsite}->{$vsite};
	if(defined $vTree->{Frontpage}->{enabled} &&
	   !defined $vTree->{Frontpage}->{passwordWebmaster}) {
		$vTree->{Frontpage}->{passwordWebmaster} = $cfg->glb('userPasswd');
	} 

}
}
	
my $migrate = {};
TreeXml::addNode('migrate', $tree, $migrate);
TreeXml::writeXml($migrate, $outFile);
exit 0;

sub dropFalse
{
	my $hash = shift;
	foreach my $item (keys %{ $hash }) {
		if(ref($hash->{$item}) eq 'HASH') {
			dropFalse($hash->{$item});
		} else {
			delete $hash->{$item} if($hash->{$item} eq 'f');
		}
	}
	return $hash;
}

sub convertBoolean
{
	my $hash = shift;
	foreach my $item (keys %{ $hash }) {
		if(ref($hash->{$item}) eq 'HASH') {
			convertBoolean($hash->{$item});
		} elsif($hash->{$item} eq 't') {
			$hash->{$item} = 1;
		} elsif($hash->{$item} eq 'f') {
			$hash->{$item} = 0;
		}
	}
	return $hash;
}

sub convertClass
{
	my $hash = shift;
	my $class = shift;

	if(defined $classData{$class}) {
		my $classHash = $classData{$class};
		foreach my $name (keys %{ $classHash }) {
			#warn "$name => ", $classHash->{$name}, "\n";
			if(defined($hash->{ $classHash->{$name} })) {
				$hash->{$name} = $hash->{ $classHash->{$name} };	
				delete $hash->{ $classHash->{$name} };
			}
		}
	} 
	if(defined $remapData{$class}) {
		my $remapHash = $remapData{$class};
		my $oldVal;
		foreach my $name (keys %{ $remapHash }) {
			next if(!defined $hash->{$name}); 
			$oldVal = $hash->{$name};	
			$hash->{$name} = $remapHash->{$name}->{$oldVal};
		}
	}
	return $hash;
}

sub convertNamespace
{
	my $hash = shift;
	my $class = shift;

	return $hash if(!defined($nameSpaceData{$class}));

	my $spaces = $nameSpaceData{$class};
	foreach my $name (keys %{ $spaces }) {
		my $ref = {};
		#warn "NAME: $name CLASS: $class\n";
		foreach my $newAttr (keys %{ $spaces->{$name} }) {
			my $oldAttr = $spaces->{$name}->{$newAttr};
			next if(!defined $hash->{$oldAttr});
			#warn "ATTR: $newAttr => ", $spaces->{$name}->{$newAttr}, "\n";
			#warn "VALUE: ", $hash->{ $spaces->{$name}->{$newAttr} }, "\n";
			$ref->{$newAttr} = $hash->{$oldAttr};
			delete $hash->{$oldAttr};
		}
		$hash->{$name} = $ref if(scalar(keys %{ $ref }) != 0);
	}
	return $hash;
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
