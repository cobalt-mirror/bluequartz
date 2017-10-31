#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: network_destroy.pl,v 1.5 2001/12/04 07:28:44 pbaltz Exp $
#
# ensure that there is always an interface or alias with the IP address of
# each vsite

use CCE;
use Vsite;

my $cce = new CCE;
$cce->connectfd();

# get network info
my $network = $cce->event_object();
my $old_network = $cce->event_old();

# make sure there are no vsites using the ip address that is going away
# use the old ip address value, because that is always available
# and will be correct in case enabled and ipaddr are changing simultaneously
my @vsites = $cce->find('Vsite', { 'ipaddr' => $old_network->{ipaddr} });
my @networks = $cce->find('Network', 
            { 
                'ipaddr' => $old_network->{ipaddr},
                'enabled' => 1
             });

if (!$network->{enabled} && scalar(@vsites) && !scalar(@networks))
{
    # just fail if it is an alias
    if (!$network->{real})
    {
        &fail($cce);
    }
    else
    {
        # interface is going down, try to migrate to alias on another real
        # interface
        my $candidate = &get_candidate_iface($cce);
    
        if (!$candidate) { &fail($cce); }
        vsite_add_network_interface($cce, $old_network->{ipaddr}, $candidate);
    }
        
}
elsif ($old_network->{ipaddr} && scalar(@vsites) &&
        $network->{ipaddr} ne $old_network->{ipaddr})
{
    # make sure there is an interface for this ip after the change
    $network->{device} =~ /^([^:]+)/;
    vsite_add_network_interface($cce, $old_network->{ipaddr}, $1);
}

$cce->bye('SUCCESS');
exit(0);

sub get_candidate_iface
{
    if (! -f "/proc/user_beancounters") { 
            $DEFAULT_INTERFACE = 'eth0'; 
    } 
    else { 
            $DEFAULT_INTERFACE = 'venet0'; 
    } 
}

sub fail
{
    my $cce = shift;

    $cce->bye('FAIL', '[[base-vsite.vsiteUsingIpAddress]]');
    exit(1);
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
