#!/usr/bin/perl
# $Id: cceDisplay.pl 922 2003-07-17 15:22:40Z will $

# Jeff's script for cce type of tests;

use strict;

use lib "/usr/sausalito/perl";
use lib "/usr/cmu/perl";
use CCE;
use Data::Dumper;
use TreeXml;

use vars qw($tree);

my $cce = new CCE;
$cce->connectuds();

my $outFile;
if(@ARGV) {
	$outFile = $ARGV[0];
} else {
	die "You must provide a file name\n";
}

dumpAll();

$cce->bye("bye");
writeXml($tree, $outFile);

exit 0;

sub dumpAll
{
	use Data::Dumper;
	my @classes = $cce->classes();		
	foreach my $class (@classes) {
		print "\nCLASS: ", $class, "\n";
		my @oids = $cce->find($class);
		foreach my $oid (@oids) {
			my ($ok, $cceObj) = $cce->get($oid);
			if($class eq 'Vsite') {
				print Dumper($cceObj);
			}
			foreach my $k (keys %{ $cceObj }) {
				print "$k => ", $cceObj->{$k}, "\n";
				$tree->{$class}->{$k} = $cceObj->{$k};
			}
			my (@namespace);
			($ok, @namespace) = $cce->names($oid);
			foreach my $name (@namespace) {
				my $nsObj;
				($ok, $nsObj) = $cce->get($oid, $name);
				foreach my $i (keys %{ $nsObj }) {
					$tree->{$class}->{$name}->{$i} = $nsObj->{$i};
				}

			}
		}
	}
			

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
