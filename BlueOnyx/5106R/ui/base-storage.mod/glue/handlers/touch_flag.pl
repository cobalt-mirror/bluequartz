#!/usr/bin/perl -w -I /usr/sausalito/perl -I /usr/sausalito/handlers/base/storage
# $Id: touch_flag.pl,v 1.3 2001/10/24 18:12:57 will Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# touch the marker on a disk when it is setup

use CCE;
use Sauce::Util;
use Base::Disk qw(disk_getmounts);
use Storage qw(storage_mount storage_umount);

my $DEBUG = 0;

my $cce = new CCE('Domain' => 'base-storage');
$cce->connectfd();

my $disk = $cce->event_object();

# don't worry if no fsType or it is a cd (read-only) or if internal
if (!$disk->{fsType} || $disk->{fsType} =~ /^iso9660$/ || $disk->{internal})
{
    $cce->bye('SUCCESS');
    exit(0);
}

my $mounts = disk_getmounts();
my $mounted = 0;

if (!$mounts->{$disk->{device}})
{
    my $disk_stats = storage_mount($disk, 'nocheck');
    if (!defined($disk_stats))
    {
        $DEBUG && warn("couldn't mount $disk->{device}");
        $cce->bye('FAIL', 'cantTouchFlag', { 'device' => $disk->{device} });
        exit(1);
    }

    $disk->{mountPoint} = $disk_stats->{MountPoint};
    $mounted = 1;
}

if (!$disk->{new})
{
    # touch the file that indicates we've seen this disk
    if (!Sauce::Util::editfile("$disk->{mountPoint}/.cobalt_storage",   
                                *touch_flag))
    {
        $DEBUG && warn("couldn't edit file $disk->{mountPoint}/.cobalt_storage");
        $cce->bye('FAIL', 'cantTouchFlag', { 'device' => $disk->{device} });
        exit(1);
    }
}
else
{
    Sauce::Util::unlinkfile("$disk->{mountPoint}/.cobalt_storage");
}

if ($mounted)
{
    storage_umount($disk->{device});
}

$cce->bye('SUCCESS');
exit(0);

sub touch_flag
{
    my ($in, $out) = @_;
    
    print $out "Do not remove this file.  It is a marker to indicate this ";
    print $out "has been setup.\n";

    return 1;
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
