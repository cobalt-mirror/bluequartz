#!/usr/bin/perl
# $Id: cceDisplay.pl 938 2004-08-18 09:16:18Z anders $

# Jeff's script for cce type of tests;

use strict;

use lib "/usr/sausalito/perl";
use CCE;
use Data::Dumper;

my $cce = new CCE;
$cce->connectuds();

dumpAll();

$cce->bye("bye");

exit 0;

sub dumpAll
{
	my ($ok, $cceObj);
	my @classes = $cce->classes();		

	foreach my $class (@classes) {
		print "--------\nCLASS: $class\n--------\n";
		my @oids = $cce->find($class);
		foreach my $oid (@oids) {
			print "--------\n BASE\n--------\n";	
			($ok, $cceObj) = $cce->get($oid);
			print Dumper($cceObj);
			print "--------\n END BASE\n--------\n";	
			my @namespace;
			print "--------\n NAMESPACES\n--------\n";	
			($ok, @namespace) = $cce->names($oid);
			foreach my $name (@namespace) {
				my $nsObj;
				($ok, $nsObj) = $cce->get($oid, $name);
				print Dumper($nsObj);
			}
			print "--------\n END NAMESPACES\n--------\n";	
		}
		print "--------\n END CLASS\n--------\n";	
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
