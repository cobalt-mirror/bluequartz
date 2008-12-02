#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/storage
# $Id: mount.pl,v 1.15.2.1 2002/03/05 01:14:29 pbaltz Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# handler to mount/umount added storage on demand
# will not allow an internal drive to be unmounted

use CCE;
use Storage qw(storage_mount storage_umount storage_set_quota);
use Base::Disk qw(disk_getmounts disk_get_usage_info);
use Sauce::Util;

my $DEBUG = 0;
if ($DEBUG) { use Data::Dumper; }

my $cce = new CCE('Domain' => 'base-storage');
$cce->connectfd();

my $disk = {};
if ($cce->event_is_destroy())
{
    $disk = $cce->event_old();
    $disk->{quota} = 0;
}
else
{
    $disk = $cce->event_object();
}

# just succeed for internal disks
if ($disk->{internal})
{
    $cce->bye('SUCCESS');
    exit(0);
}

my $check_disk = ($disk->{check} ? '' : 'nocheck');
my $mounts = disk_getmounts();

my $ok;
if (!$cce->event_is_destroy() && $disk->{mount})
{
    my $disk_info = {};
    
    # only try to mount if not mounted already
    if (!$mounts->{$disk->{device}})
    {
        $disk_info = storage_mount($disk, $check_disk);

        if (not $disk_info)
        {
            $cce->bye('FAIL', 
                "[[base-storage.cantMount,device='$disk->{device}']]");
            exit(1);
        }
    }
    else
    {
        # get the disk usage information
        my $disk_stats = disk_get_usage_info($disk->{device});
        $disk_info = $disk_stats->{$disk->{device}};
    }
        
    # need this later for quota
    $disk->{mountPoint} = $disk_info->{MountPoint};
    
    # update usage and total information
    ($ok) = $cce->set($cce->event_oid(), '', 
                {
                    'mountPoint' => $disk_info->{MountPoint},
                    'used' => $disk_info->{Used},
                    'total' => $disk_info->{Total},
                    'mounted' => 1
                });
    if (not $ok)
    {
        $cce->warn('[[base-storage.cantUpdateDiskInfo]]');
    }
}
elsif (($cce->event_is_destroy() && $disk->{mounted}) ||
        ($disk->{mounted} && !$disk->{mount} && !$disk->{internal}))
{
    $DEBUG && print STDERR "umounting $disk->{device}\n";

    # unmount partition
    my $ret = storage_umount($disk->{device});

    if (not $ret)
    {
        $cce->bye('FAIL', "[[base-storage.cantUmount,device='$disk->{device}']]");
        exit(1);
    }

    if (!$cce->event_is_destroy())
    {
        ($ok) = $cce->set($cce->event_oid(), '',
                {
                    'mounted' => 0
                });
        if (not $ok)
        {
            $cce->warn('[[base-storage.cantUpdateMountInfo]]');
        }
    }
}

# deal with quota, if not a create, or
# the disk is being mounted
if (!$cce->event_is_create() || $disk->{mount})
{
    $DEBUG && print STDERR "setting up quota\n";

    $ok = storage_set_quota($disk);

    if ($disk->{quota} && $ok && $ok == -1)
    {
        $cce->bye('FAIL', 'quotaUnsupported', { 'fstype' => $disk->{fsType} });
        exit(1);
    }
    elsif (not $ok)
    {
        if ($disk->{quota})
        {
            $cce->bye('FAIL', '[[base-storage.cantEnableQuotas]]');
            exit(1);
        }
        else
        {
            $cce->bye('FAIL', '[[base-storage.cantDisableQuotas]]');
            exit(1);
        }
    }
} # end if (!$cce->event_is_create() || $disk->{mount})

# delete the mountpoint directory for this disk on destroy
# if no other disk is mounted there
my @other_disks = $cce->find('Disk', { 'mountPoint' => $disk->{mountPoint} });

$DEBUG && print STDERR Dumper($disk, \@other_disks);
if ((!$disk->{mount} || $cce->event_is_destroy()) && $disk->{mountPoint}
    && (scalar(@other_disks) <= 1) && (-d $disk->{mountPoint}))
{
    system('/bin/rmdir', $disk->{mountPoint});
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
