#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: no_duplicates.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# don't allow the creation of Network aliases with the same IP address
# as another alias or a real interface.  Also don't allow a real interface
# to have it's IP address changed to that of another real interface.

use CCE;

my $cce = new CCE('Domain' => 'base-network');
$cce->connectfd();

my $new_net = $cce->event_object();

# make sure the device is unique
my @devices = $cce->find('Network', { 'device' => $new_net->{device} });

# there should be only the current object found above
if (scalar(@devices) > 1)
{
	$cce->bye('FAIL', 'noDuplicateDevices', { 'device' => $new_net->{device} });
	exit(1);
}

# do things differently if this is real or an alias
my $real = 0;
if ($new_net->{device} =~ /^eth\d$/)
{
    $real = 1;
}

my @old_nets = $cce->find('Network', 
			  { 
			      'ipaddr' => $new_net->{ipaddr},
			      'enabled' => 1
			      });

# look for just real interfaces, in case the interface being modified is real
my @real_nets = $cce->find('Network',
                        {
                            'ipaddr' => $new_net->{ipaddr},
                            'enabled' => 1,
                            'real' => 1
                        });

# only bother if it's enabled and it has a real ip address assigned
if ($new_net->{enabled} && ($new_net->{ipaddr} ne '') && 
	($new_net->{ipaddr} ne '0.0.0.0'))
{

    # fail if it's an alias and a Network object exists with the same IP
    # also fail if it's real and another real interface already has this IP
    if (((scalar(@old_nets) > 1) && !$real) || 
        ((scalar(@real_nets) > 1) && $real))

    {
        $cce->bye('FAIL', 'noDuplicateIPsAllowed', 
                            { 'ipaddr' => $new_net->{ipaddr} });
        exit(1);
    }
} # end if enabled with a valid ip address

# otherwise, it is okay
$cce->bye('SUCCESS');
exit(0);
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
