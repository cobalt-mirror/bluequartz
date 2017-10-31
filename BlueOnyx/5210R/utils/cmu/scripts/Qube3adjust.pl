#!/usr/bin/perl
# $Id: Qube3adjust.pl 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http::/www.cobalt.com
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'adjust');
$cfg->parseOpts();

use TreeXml;
require CmuCfg;
require Archive;

use vars qw(%classData %nameSpaceData %remapData);

my %classData = (
	user => {
		siteAdministrator => 'admin',
		fullName => 'fullname',
	},
);	

my %nameSpaceData = (
	group	=>	{ 
		Disk => { quota => 'quota' },
	},
	user => { 
		Disk => { quota => 'quota' }, 
		Email => {
			aliases => 'aliases',
			forwardEnable => 'forwardEnable',
			forwardEmail => 'forwardEmails',
			vacationOn => 'vacationOn',
			vacationMsg => 'vacationMsg',
		},
		AddressbookEntry => {
			phone => 'phone',
			fax => 'fax',
			homeUrl => 'homeUrl',
			address => 'address',
		},
	},
	list => {
		Archive => {
			enabled => 'enabled',
			keep_for_days => 'keep_for_days',
		}
	},
);

my %remapData = ();

my ($cmuXml, $outFile, $arch);
if($cfg->isSess) {
	$cmuXml = $cfg->cmuXml;
	$outFile = $cfg->sessXml;
	if(-f $outFile) { $cmuXml = $outFile; }
} else { die "ERROR: no session id given, cannot adjust files\n" }


my $tree = TreeXml::readXml($cmuXml, 0);

$tree->{adjustPlatform} = "Qube3";

if(defined $tree->{user} && $cfg->confOnly eq 'f') {
	foreach my $u (keys %{ $tree->{user} }) {
		$arch = Archive->new(type => 'users',
			destDir => $cfg->destDir,
			sessID => $cfg->sess,
			archives => $tree->{user}->{$u}->{archives}
		);	
		$arch->xmlAttrConvert('httpd', 'users', 'gid');
	}
}

my @classes = keys %{ $tree };
foreach my $class (@classes) {
	if(ref($tree->{$class}) eq 'HASH') {
		my @objs = keys %{ $tree->{$class} };
		foreach my $obj (@objs) {
			convertClass($tree->{$class}->{$obj}, $class);
			convertNamespace($tree->{$class}->{$obj}, $class);
		}
	} 
}
my $migrate = {};
TreeXml::addNode('migrate', $tree, $migrate);
TreeXml::writeXml($migrate, $outFile);
exit 0;


sub convertClass
{
	my $hash = shift;
	my $class = shift;

	return $hash if(!defined($classData{$class}));

	my $classHash = $classData{$class};
	foreach my $name (keys %{ $classHash }) {
		#warn "$name => ", $classHash->{$name}, "\n";
		if(defined($hash->{ $classHash->{$name} })) {
			$hash->{$name} = $hash->{ $classHash->{$name} };	
			delete $hash->{ $classHash->{$name} };
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
			next if(!defined($hash->{$oldAttr}));
			#warn "ATTR: $newAttr => ", $spaces->{$name}->{$newAttr}, "\n";
			#warn "VALUE: ", $hash->{ $spaces->{$name}->{$newAttr} }, "\n";
			#remap value if needed 
			if(defined($remapData{$oldAttr}->{ $hash->{$oldAttr} }))  {
				$ref->{$newAttr} = $remapData{$oldAttr}->{ $hash->{$oldAttr} };
			} else { $ref->{$newAttr} = $hash->{$oldAttr} };
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
