#!/usr/bin/perl -I /usr/sausalito/perl
#
# $Id: validate_netmask.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
#
# make sure a netmask is valid (ie leading bits are all 1's and once a 0
# is found all the trailing bits are 0's)
#
# handles both Network.netmask and Route.netmask
#

use CCE;

my $cce = new CCE('Domain' => 'base-network');
$cce->connectfd();

my $network = $cce->event_object();

# if netmask is blank, we have nothing to say
if ($network->{netmask} eq '') {
	$cce->bye('SUCCESS');
	exit(0);
}

# setup the error message different for real and alias interfaces
my @pre_error = ();
if (($network->{CLASS} eq 'Network') && $network->{real}) {
	@pre_error = ('invalidNetmaskReal',
		{
			'device' => '[[base-network.interface' .
					$network->{device} . ']]',
			'netmask' => $network->{netmask}
		});
} else {
	# aliases get a generic error message
	@pre_error = ('invalidNetmaskAlias',
		{ 'netmask' => $network->{netmask} });
}

		
my @netmask = split(/\./, $network->{netmask});

# must be at least a subnet of a class A network
if ($netmask[0] != 255) {
	$cce->bye('FAIL', @pre_error);
	exit(1);
}

my $in_zero_part = 0;
for (my $i = 1; $i < scalar(@netmask); $i++) {
	if ($in_zero_part && ($netmask[$i] != 0)) {
		$cce->bye('FAIL', @pre_error);
		exit(1);
	} elsif ($netmask[$i] != 255) {
		if (&is_octet_valid($netmask[$i])) {
			$in_zero_part = 1;
		} else {
			$cce->bye('FAIL', @pre_error);
			exit(1);
		}
	}
}

$cce->bye('SUCCESS');
exit(0);

sub is_octet_valid
{                                                                               
        my $octet = shift;

        # check for edge case
	if ($octet == 0) {
		return 1;
	}

	my $sum = 0;                                                            
        for (my $j = 7; $j >= 0; $j--) {                                        
                $sum += 2 ** $j;                                                 
                if ($octet == $sum) {                                           
                        return 1;                                               
                }                                                               
        }                                                                       
        return 0;                                                               
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
