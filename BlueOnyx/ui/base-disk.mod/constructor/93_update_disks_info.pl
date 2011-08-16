#!/usr/bin/perl
# $Id: 93_update_disks_info.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# makes sure a Disk object exists for each internal logical partition, 
# and that each Disk's total space and used space are up to date
#

use strict;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/disk);
use CCE;
use Base::Disk qw(disk_getmounts disk_get_usage_info);
use Base::HomeDir;

my $cce = new CCE('Domain' => 'base-disk');

$cce->connectuds();

my $mounts = disk_getmounts();

for my $partition (keys %$mounts)
{
    # this script only deals with internal disks
    next unless ($partition =~ /^\/dev/);

    my ($oid) = $cce->find("Disk", { 'device' => $partition });

    # get disk usage information
    my $info = disk_get_usage_info($partition);

    if ($oid)
    {
        $cce->set($oid, "", { 'total' => $info->{$partition}->{Total}, 
                            'used' => $info->{$partition}->{Used} });
    }
    else
    {
        my $partition_info = { 
                            'device' => $partition,
                            'mountPoint' => $info->{$partition}->{MountPoint},
                            'total' => $info->{$partition}->{Total},
                            'used' => $info->{$partition}->{Used},
                            'mounted' => 1,
                            'internal' => 1
                        };

        if($info->{$partition}->{MountPoint} =~ /$Base::HomeDir::HOME_ROOT/)
        {
            $partition_info->{isHomePartition} = 1;
        }
       
        my ($ok) = $cce->create("Disk", $partition_info);

        if (not $ok)
        {
            $cce->bye('FAIL', 'cantCreateDisk', { 'device' => $partition });
            exit(1);
        }
    }
}

# Find /home in CCE and if it is not there, create a simulated /home instead.
# We really need one (at least simulated) or we cannot create Vsites:

my @oids = $cce->find('Disk', { 'mountPoint' => '/home' });
if ($#oids < 0) {  

            # Populate CCE:   
            my ($ok) = $cce->create('Disk', {
                'isHomePartition' => '1',
                'mounted' => '1',
                'fsType' => '',
                'mountPoint' => '/home', 
                'CLASS' => 'Disk',
                'quota' => '0',
                'uuid' => '',
                'device' => '/dev/simfs',
                'label' => '',
                'check' => '0',
                'internal' => '1',
                'mount' => '0'
                    });
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
