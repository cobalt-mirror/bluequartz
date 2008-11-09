#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: df.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# original author: Kevin K.M. Chiu
# rewritten by: someone else
#
# There is no longer a System.Disk namespace, so now this runs on the 
# Disk.refresh property

use CCE;
use Base::Disk qw(disk_get_usage_info);

my $cce = new CCE('Domain' => 'base-disk');
$cce->connectfd();

my $disk = $cce->event_object();

# don't bother if the disk isn't mounted
if (not $disk->{mounted})
{
    $cce->bye('SUCCESS');
    exit(0);
}

# df.  use mountPoint in case it is a network file system
my $usage_info = disk_get_usage_info($disk->{mountPoint});

if ($usage_info == undef)
{
    $cce->bye('FAIL', '[[base-disk.cantExecuteDf]]');
    exit(1);
}

# write result to CCE
my ($ok) = $cce->set($cce->event_oid(), '', 
                {
                    'total' => $usage_info->{$disk->{device}}->{Total},
                    'used' => $usage_info->{$disk->{device}}->{Used}
                });

if(!$ok) 
{
    $cce->bye('FAIL', 'cantUpdateDiskInfo', { 'device' => $disk->{device} });
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
