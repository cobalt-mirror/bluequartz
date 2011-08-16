#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/storage
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: setup_web_access.pl,v 1.2 2001/10/16 17:19:16 pbaltz Exp $
#
# make sure that additional storage setup as a directory for sites
# is added to the access configuration file, so that user's web pages
# are accessible

use CCE;
use Sauce::Service;
use Base::Httpd qw(httpd_set_access_rules);
use Storage qw(storage_get_mount_point);

my $cce = new CCE('Domain' => 'base-storage');
$cce->connectfd();

my $disk = $cce->event_object();
my $new_disk = $cce->event_new();

if ($cce->event_is_destroy())
{
    $disk = $cce->event_old();
}

# don't bother if the disk is internal
if ($disk->{internal})
{
    $cce->bye('SUCCESS');
    exit(0);
}

# check for whether the sections should be removed or added
my $remove = 0;
if ($cce->event_is_destroy() || !$disk->{isHomePartition} || 
    (exists($new_disk->{mount}) && !$new_disk->{mount}))
{
    $remove = 1;
}

# modify the section for the .sites directory
my $info = {
            'Options' => [ 
                            'Indexes', 'FollowSymLinks', 
                            'Includes', 'MultiViews'
                            ],
            'AllowOverride' => [ 'AuthConfig', 'Indexes', 'Limit' ],
            'FileSections' => { '.ht*' => { 'deny' => [ 'all' ] } },
            'order' => [ 'allow', 'deny' ],
            'allow' => [ 'all' ]
            };

my $dir = storage_get_mount_point($disk) . '/.sites/';
if (!httpd_set_access_rules('Directory', $dir, ($remove ? '' : $info)))
{
    $cce->bye('FAIL', 'cantSetupApacheConfig');
    exit(1);
}

# modify section for individual sites
$info = { 'Options' => [ '-FollowSymLinks', '+SymLinksIfOwnerMatch' ] };
$dir .= '*/*/';

if (!httpd_set_access_rules('Directory', $dir, ($remove ? '' : $info)))
{
    $cce->bye('FAIL', 'cantSetupApacheConfig');
    exit(1);
}

# restart the public server
service_run_init('httpd', 'reload');

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
