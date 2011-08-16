#!/usr/bin/perl -I/usr/sausalito/perl 
# $Id: processAdvise.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# 
# processAdvise.pl - Defines maximum number of Apache processes, the max spare
#  process count, & the min spare process count.
#
# Algorithms by Adrian, Tim & Will.

use CCE;

my $cce = new CCE;
$cce->connectuds();

my @oids = $cce->find('System');
if (not @oids) {
	$cce->bye('FAIL');
	exit 1;
}

my ($ok, $obj) = $cce->get($oids[0], 'Web');
unless ($ok and $obj) {
	$cce->bye('FAIL');
	exit 1;
}

my $minSpare = $obj->{'minSpare'};
my $maxSpare = $obj->{'maxSpare'};
my $maxClients = $obj->{'maxClients'};
    
my $totmem;
open( MEM, "/proc/meminfo" );
while (<MEM>) {
	if (/^MemTotal:\s+(\d+)/) {
		$totmem=$1
	}
}
close(MEM);
if (!$totmem) { $totmem = 65536; }

#
# Adrian and Tim's special formula
# Only allow 75% of physical RAM for apache processes, and assume 3 MB per
# apache process
#
my $server_procs = int((($totmem / 1024) * .75) / 3);

my $minSpareAdvised = $server_procs;
$minSpareAdvised = 50 if ($minSpareAdvised > 50);
my $maxSpareAdvised = $server_procs;
$maxSpareAdvised = 100 if ($maxSpareAdvised > 100);
my $maxClientsAdvised = 2*$server_procs;

# Build an argument hash
my %update = (
	'minSpareAdvised' => $minSpareAdvised,
	'maxSpareAdvised' => $maxSpareAdvised,
	'maxClientsAdvised' => $maxClientsAdvised,
	);

# detect virgin system & set http process counts
if($obj->{'minSpareAdvised'} eq '0') { 
	my $max = 2*$server_procs;
	$max = 125 if ($max > 125);
	$max = 30 if ($max < 30);

	$update{'minSpare'} = 10;
	$update{'maxSpare'} = 25;
	$update{'maxClients'} = $max;
}

$cce->set($oids[0], 'Web', \%update);

$cce->bye('SUCCESS');

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
