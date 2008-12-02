#!/usr/bin/perl -w -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: watch_disk.pl,v 1.4 2001/11/05 22:02:03 pbaltz Exp $
#
# make sure that additional storage that sites are located on cannot
# be modified or removed so that the sites become unusable

use CCE;

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my $disk = $cce->event_object();
if ($cce->event_is_destroy())
{
    $disk = $cce->event_old();
}

# just succeed for the /home partition
if ($disk->{mountPoint} eq '/home')
{
    $cce->bye('SUCCESS');
    exit(0);
}

my @sites = $cce->findx('Vsite', 
                    { 'volume' => $disk->{mountPoint} });

if (scalar(@sites))
{
    my @site_names = ();
    for my $site (@sites)
    {
        my ($ok, $site_info) = $cce->get($site);
        push @site_names, $site_info->{fqdn};
    }
    
    $cce->bye('FAIL', 'diskUsedBySites', 
                { 'sites' => (join(', ', @site_names)) });
    exit(1);
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
