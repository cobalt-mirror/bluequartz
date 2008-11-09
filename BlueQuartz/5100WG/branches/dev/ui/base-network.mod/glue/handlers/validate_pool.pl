#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/network
# $Id: validate_pool.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# 

use CCE;
use IpPooling;

my $cce = new CCE('Domain' => 'base-network', 'Namespace' => 'Network');
$cce->connectfd();

my $network = $cce->event_object();

if ($network->{pooling}) {
    my (@oids) = $cce->findx('IPPoolingRange');
    my @range_oids = ();
    
    # get ranges
    foreach $a_oid (@oids) {
	my ($ok, $range) = $cce->get($a_oid);
	push @ranges, $range;
    }
    
    # get networks
    my (@network_oids) = $cce->findx('Network', { 'enabled' => 1 });
    foreach my $oid (@network_oids) {
	my ($ok, $net) = $cce->get($oid);
	if (!$ok) {
	    $cce->bye('FAIL');
	    exit 1;
	}
	push @ips, $net->{ipaddr};
    }

    my (@error_ips) = IpPooling::validate_pooling_state(\@ranges, \@ips);
    if (@error_ips) {
	$cce->warn('cant_enable_pooling', { 'ips_not_covered' => join(',', @error_ips)});
	$cce->bye('FAIL');
	exit 1;
    }
}

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
