#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ssl
# $Id: vsite_ssl.pl 1392 2010-03-04 07:35:26Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# update siteAdminCaps property when enabling/disabling ssl

my $DEBUG = 0;

use File::Copy;
use CCE;
use Base::Vsite qw(vsite_update_site_admin_caps);

my $cce = new CCE('Domain' => 'base-ssl');
$cce->connectfd();

# get vsite and ssl information for the vsite
my $vsite = $cce->event_object();
my $oid = $cce->event_oid();
my ($ok, $ssl_info) = $cce->get($oid, 'SSL');
$DEBUG && print STDERR Dumper($vsite, $ssl_info);

# make sure two sites with the same IP address don't both get SSL enabled
my @other_sites = $cce->findx('Vsite', 
                        { 'ipaddr' => $vsite->{ipaddr}, 'SSL.enabled' => 1 });

if ($ssl_info->{enabled} && scalar(@other_sites) > 1)
{
    my $oid_to_get = $other_sites[0] != $oid ? $other_sites[0] : $other_sites[1];
    ($ok, my $other_site) = $cce->get($oid_to_get);
                                
    $cce->bye('FAIL', 'sslAlreadyUsedByIP', 
                { 
                    'fqdn' => $other_site->{fqdn}, 
                    'ipaddr' => $other_site->{ipaddr} 
                });
    exit(1);
}

# set VirtualHost.ssl
my ($void) = $cce->find('VirtualHost', {'name' => $vsite->{name}});
$cce->set($void, '', {'ssl' => $ssl_info->{enabled}});

# update siteAdminCaps in Vsite
if (!$cce->event_is_destroy())
{
    vsite_update_site_admin_caps($cce, $vsite, 'siteSSL', $ssl_info->{enabled});
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
