#!/usr/bin/perl -w
# $Id: setup_capabilities.pl
# setup all necessary capability groups for the product
# this maybe should go elsewhere or the really detailed stuff should 
# be read in, maybe?

use strict;
use lib qw(/usr/sausalito/perl);
use CCE;

my $cce = new CCE;
$cce->connectuds();

# create all minor groups
my @groups = (
        {
            'name' => 'menuServerNetworkServices',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                    'serverHttpd', 'serverFTP',
                    'serverEmail', 'serverDNS',
                    'serverSNMP', 'serverShell',
                    'serveriStat'
                    )
        },
        {
            'name' => 'menuServerSecurity',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                    'serverSSL', 'serverConfig')
        },
        {
            'name' => 'menuServerSystem',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                    'serverNetwork', 'serverIpPooling',
                    'serverVsite', 'serverPower',
                    'serverTime', 'serverInformation',
		    'serverMemcache')
        },
        {
            'name' => 'menuServerMaintenance',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                    'serverServerDesktop')
        },
        {
            'name' => 'menuServerServerStats',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                    'serverStatsServerNetwork',
                    'serverStatsServerWebalizer',
                    'serverStatsServerWeb',
                    'serverStatsServerFTP',
                    'serverStatsServerEmail',
                    'serverStatsServerDisk')
        },
        {
            'name' => 'menuServerActiveMonitor',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                    'serverShowActiveMonitor',
                    'serverActiveMonitor')
        },
        {
            'name' => 'serverManage',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                    'menuServerNetworkServices',
                    'menuServerSecurity',
                    'menuServerSystem',
                    'menuServerMaintenance',
                    'menuServerServerStatus',
                    'menuServerActiveMonitor')
        },
        {
            'name' => 'siteAdmin',
            'shown' => 1,
            'capabilities' => ''
        },
        {
            'name' => 'adminUser',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                                'siteAdmin', 'siteSSL',
                                'siteAnonFTP', 'siteShell'
                              )
        }
            );

# now loop through all the capability groups and create as necessary
for my $cap (@groups)
{
    my ($oid) = $cce->find('CapabilityGroup', { 'name' => $cap->{name} });

    if (not $oid)
    {
        my ($ok) = $cce->create('CapabilityGroup', $cap);
        if (not $ok)
        {
            print STDERR "Failed to create $cap->{name}\n";
        }
    } else {
    # reset capabilities
        my ($ok) = $cce->set($oid, "",
                                {
                                    capabilities => $cap->{capabilities}
                                });
        if (!$ok) {
            print STDERR "Failed to update $cap->{name}\n";
        }
    }
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
