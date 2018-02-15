#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: dns.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# dns.pl
# This handler make sure that the Qube has at least one DNS server
# specified if it is connected to the internet in any way.  This
# is mostly for the case when the network is auto-configured at
# first boot.  This helps users who may not be aware of the need
# for a DNS server to use the Qube as a router.

use CCE;

my $cce = new CCE;

$cce->connectfd();

my $system = $cce->event_object();
my ($ok, $sysnet) = $cce->get($cce->event_oid(), 'Network');

if (not $ok) 
{
    $cce->warn('[[base-network.cantGetNetwork]]');
    $cce->bye('FAIL');
    exit(1);
}

# if the internetMode is anything other than none, make sure
# there is a DNS server specified.  If there isn't, make the
# Qube its own DNS server.
if ($sysnet->{internetMode} ne 'none') 
{
    # check to make sure we have at least one dns server specified
    my @dns_servers = $cce->scalar_to_array($system->{dns});

    if (scalar(@dns_servers) > 0) 
    {
        # already have dns servers specified
        $cce->bye('SUCCESS');
        exit(0);
    }

    # other wise, there is currently no DNS server specified
    # insert local host as dns server
    ($ok) = $cce->set($cce->event_oid(), '', { 'dns' => '&127.0.0.1&' });
    
    if (not $ok) 
    {
        $cce->warn('[[base-network.couldntChangeDns]]');
        $cce->bye('FAIL');
        exit(1);
    }

    # start the DNS server
    ($ok) = $cce->set($cce->event_oid(), 'DNS', { 'enabled' => 1 });

    if (not $ok) 
    {
        $cce->warn('[[base-network.couldntStartDns]]');
        $cce->bye('FAIL');
        exit(1);
    }
}

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
