#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: 31_handle_aws_dhcp.pl
#
# On a regular BlueOnyx install this script does nothing and exits almost right away.
# It is only needed on AWS Clouds or in an environment where the network settings
# are gathered from DHCP.
#
# If DHCP is used, it will do the following:
#
# 1.) It will read in the currently used IP for eth0 and will store it for usage in the "Virtual Site Template".
# 2.) It will reconfigure all Vsites to run on that IP.
# 3.) It will update the Proftpd config file to use the new IP.

# Debugging switch:
$DEBUG = "0";

# Are we using DHCP?
if (!-f "/etc/is_aws") {
    # Apparently not. Great!
    exit(0);
}

use CCE;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';
$cce->connectuds();

#
#### No configureable options below!
#

# Get eth0 IP:
($sys_oid) = $cce->find('Network', {'device' => 'eth0'});
($ok, $eth0) = $cce->get($sys_oid);
$ip = $eth0->{'ipaddr'};

if ($ip) {

    # Update IP in Virtual Site Template:
    ($sys_oid) = $cce->find('System');
    ($ok, $sys) = $cce->get($sys_oid);
    ($ok) = $cce->set($sys_oid, 'VsiteDefaults',{ 'ipaddr' => $ip });

    # Find all Vsites:
    my @vhosts = ();
    my (@vhosts) = $cce->findx('Vsite');
 
    # Walk through all Vsites and update their IP's if necessary:
    for my $vsite (@vhosts) {
        ($ok, my $my_vsite) = $cce->get($vsite);
        if ($my_vsite->{'ipaddr'} ne $ip) {
            ($ok) = $cce->set($vsite, '', { 'ipaddr' => $ip });
        }
    }

}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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