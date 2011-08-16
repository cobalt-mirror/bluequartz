#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/network
# $Id: destroy_range.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# If pooling is enabled, see if we can delete this range.
# If after deleting this range, all the ranges still encompass all the ips,
# then we can delete this. Otherwise, we're not allowed to delete this.

use CCE;
use IpPooling;

my $cce = new CCE('Domain' => 'base-network');
$cce->connectfd();

my $old = $cce->event_old();
my $object = $cce->event_object();
my $new = $cce->event_new();

my ($sysoid) = $cce->findx('System');
my ($ok, $network) = $cce->get($sysoid, 'Network');

# check against network interfaces if pooling is enabled
if ($network->{pooling}) {
    
    my (@oids) = $cce->find('IPPoolingRange');
    my @ranges = ();
    
    # we know we're being deleted
    # so get all the OTHER ranges
    foreach $a_oid (@oids) {
	if ($a_oid != $object->{event_oid}) {
	    my ($ok, $range) = $cce->get($a_oid);
	    if (!$ok) {
		$cce->bye('FAIL');
		exit 1;
	    }
	    push @ranges, $range;;
	}
    }
    
    # get network ips
    my (@network_oids) = $cce->find('Network', { 'enabled' => 1});
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
	$cce->warn('cant_delete_range', { 'affected_ips' => join(',', @error_ips)});
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
