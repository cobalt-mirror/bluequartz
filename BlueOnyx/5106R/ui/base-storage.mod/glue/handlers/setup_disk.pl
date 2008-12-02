#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/storage
# $Id: setup_disk.pl,v 1.11 2001/10/16 17:19:16 pbaltz Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# partition (if necessary) and format a disk

use CCE;
use Storage qw(
            storage_partition storage_format 
            storage_mount storage_umount
            storage_get_uuid
            );
use Base::Disk qw(disk_getmounts);

my $DEBUG = 0;
if ($DEBUG)
{
    use Data::Dumper;
}

my $cce = new CCE('Domain' => 'base-storage');
$cce->connectfd();

my $disk = $cce->event_object();
my $new_disk = $cce->event_new();

# just succeed if the device is marked as internal
if ($disk->{internal})
{
    $cce->bye('SUCCESS');
    exit(0);
}

my $ok;

# refuse to erase or format disks if the Disk is being created
if ($cce->event_is_create() && $disk->{eraseDisk})
{
    $cce->bye('FAIL', '[[base-storage.noEraseDuringCreate]]');
    exit(1);
}

# only go through partitioning and formatting if not a create
if (!$cce->event_is_create())
{
    # no non-local or ro filesystems allowed
    if ($disk->{fsType} =~ /^(nfs|smbfs|iso9660)$/)
    {
        $cce->bye('FAIL', '[[base-storage.onlyLocalFilesystems]]');
        exit(1);
    }
    elsif (!$disk->{fsType})  # must specify filesystem type
    {
        $cce->bye('FAIL', '[[base-storage.noFsType]]');
        exit(1);
    }
    
    my $mounts = disk_getmounts();
    my @parts = keys(%$mounts);
    
    # fail if filesystem is mounted
    if (grep(/^$disk->{device}/, @parts))
    {
        $cce->bye('FAIL', 'diskIsMounted', { 'device' => $disk->{device} });
        exit(1);
    }
    
    # partition if necessary
    if ($disk->{device} =~ /\D$/ && not storage_partition($disk->{device}, 1))
    {
        $cce->bye('FAIL', 'cantPartition', { 'device' => $disk->{device} });
        exit(1);
    }
    elsif ($disk->{device} =~ /\D$/)
    {
        # partition went fine, update the device name
        $disk->{device} = "$disk->{device}1";
    }
    
    # only try to format
    if (!storage_format($disk->{device}, $disk->{fsType}, ($disk->{check} ? 'check' : '')))
    {
        $cce->bye('FAIL', 'cantFormat', { 'device' => $disk->{device} });
        exit(1);
    }
}

# should always be able to get the uuid for a partition (unless it is internal)
if ($disk->{device} =~ /\d$/ && !storage_get_uuid($disk))
{
    $DEBUG && warn("couldn't get UUID for $disk->{device}");
    $cce->bye('FAIL', '[[base-storage.cantGetUUID]]');
    exit(1);
}
else
{
    # update the device name and uuid
    my ($footest) = $cce->set($cce->event_oid(), '', 
                    { 
                        'device' => $disk->{device},
                        'uuid' => $disk->{uuid}
                    });
    if (!$footest)
    {
        $DEBUG && warn("cce->set returned $footest");
        $cce->bye('FAIL');
        exit(1);
    }
}

# that's all we do here
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
