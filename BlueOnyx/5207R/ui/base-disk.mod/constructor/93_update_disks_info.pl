#!/usr/bin/perl
# $Id: 93_update_disks_info.pl
#
# Makes sure a Disk object exists for each internal logical partition, 
# and that each Disk's total space and used space are up to date
#

use strict;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/disk);
use CCE;
use Base::Disk qw(disk_getmounts disk_get_usage_info);
use Base::HomeDir;
use Data::Dumper;

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

        #print "device: " . $partition . "\n";
        #print Dumper(\$info);
        #print "mountPoint: " . $info->{$partition}->{MountPoint} . "\n";
        #print "total: " . $info->{$partition}->{Total} . "\n";
        #print "used: " . $info->{$partition}->{Used} . "\n";

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
            #$cce->bye('WARN', 'cantCreateDisk', { 'device' => $partition });
            #exit(1);
        }
    }
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 