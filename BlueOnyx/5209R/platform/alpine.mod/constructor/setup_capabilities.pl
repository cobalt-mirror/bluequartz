#!/usr/bin/perl 
# $Id: setup_capabilities.pl
# Setup all necessary capability groups for the product

use lib qw(/usr/sausalito/perl);
use CCE;
use Data::Dumper;

$cce = new CCE;
$cce->connectuds();

$build = `cat /etc/build | grep 5209R | wc -l`;
chomp($build);

# create all minor groups
@groups = (
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
                    'serverTime', 'serverInformation')
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
                                'siteAdmin',
                                'siteSSL',
                                'siteAnonFTP', 
                                'siteShell' 
                              )
        },
        {
            'name' => 'admin',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                    'serverHttpd', 'serverFTP', 'serverEmail', 'serverDNS', 
                    'serverSNMP', 'serverShell', 'serveriStat', 'serverSSL', 
                    'serverSystemFirewall', 'serverNetwork', 
                    'serverVsite', 'serverTime', 'serverInformation', 
                    'serverServerDesktop', 'serverStatsServerNetwork', 
                    'serverStatsServerWebalizer', 'serverStatsServerWeb', 
                    'serverStatsServerFTP', 'serverStatsServerEmail', 
                    'serverStatsServerDisk', 'serverShowActiveMonitor', 
                    'serverActiveMonitor', 'manageSite','menuServerServerStats', 
                    'managePackage', 'siteAdmin', 'siteSSL', 'siteAnonFTP', 'siteShell', 'siteDNS'
                              )
        }
            );

# Reseller caps:

if ($build == "1") {
    # Special reseller caps for 5209R:
    @groups_reseller = (
        {
            'name' => 'reseller',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                'resellerPHP',
                'resellerSUPHP',
                'resellerRUID',
                'resellerFPM',
                'resellerMySQL',
                'resellerJSP',
                'resellerCGI',
                'resellerSSI',
                'resellerSSL',
                'resellerFTP',
                'resellerAnonFTP',
                'resellerShell'
            )
        }
    );
}
else {
    # Older reseller caps for 5207R/5208R:
    @groups_reseller = (
        {
            'name' => 'reseller',
            'shown' => 1,
            'capabilities' => $cce->array_to_scalar(
                'resellerPHP',
                'resellerSUPHP',
                'resellerMySQL',
                'resellerJSP',
                'resellerCGI',
                'resellerSSI',
                'resellerSSL',
                'resellerFTP',
                'resellerAnonFTP',
                'resellerShell'
            )
        }
    );
}

# Merge:
@groups = (@groups, @groups_reseller);

# Now loop through all the capability groups and create as necessary
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