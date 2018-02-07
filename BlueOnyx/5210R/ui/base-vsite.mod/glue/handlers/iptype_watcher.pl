#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: iptype_watcher.pl
#
# Handle Vsite IPv4/IPv6 allocation in case the IPType or GW changes:
#

use CCE;
use POSIX qw(isalpha);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
    use Sys::Syslog qw( :DEFAULT setlogsock);
    use Data::Dumper;
}

my $cce = new CCE('Domain' => 'base-vsite');

$cce->connectfd();

my $System = $cce->event_object();
my $System_new = $cce->event_new();
my $System_old = $cce->event_old();

my @oids= $cce->find("System");
my ($ok, $system) = $cce->get($oids[0]);

&debug_msg("Startup iptype_watcher.pl\n");

# Are we an OpenVZ master-node?
if ((-e "/proc/user_beancounters") && (-f "/etc/vz/conf/0.conf")) {
    # Yes, we are.
    $device = 'venet0:0';
}
elsif ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
    # No, we're in an OpenVZ VPS:
    $device = 'venet0';
}
else {
    # No, we are not.
    $device = 'eth0';
}

# Get primary IPs of '$device' from Network Config file:
if (($System->{IPType} eq 'VZv4') || ($System->{IPType} eq 'IPv4')) {
    $ipv6_ip = '';
}
elsif (($System->{IPType} eq 'VZv6') || ($System->{IPType} eq 'VZBOTH')) {
    $ipv6_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep IPV6ADDR_SECONDARIES= | awk -F "IPV6ADDR_SECONDARIES=" '{print \$2}'| sed 's/"//g'|awk -F '/' '{print \$1}'`;
}
else {
    $ipv6_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep IPV6ADDR= | awk -F "IPV6ADDR=" '{print \$2}'`;
}
chomp($ipv6_ip);

# Are we an OpenVZ master-node?
if ((-e "/proc/user_beancounters") && (-f "/etc/vz/conf/0.conf")) {
    # Yes, we are.
    $device = 'venet0:0';
}
elsif ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
    # No, we're in an OpenVZ VPS:
    $device = 'venet0:0';
}
else {
    # No, we are not.
    $device = 'eth0';
}

# Get primary IPs of '$device' from Network Config file:
if (($System->{IPType} eq 'VZv6') || ($System->{IPType} eq 'IPv6')) {
    $ipv4_ip = '';
}
elsif (($System->{IPType} eq 'VZv4') || ($System->{IPType} eq 'VZBOTH')) {
    $ipv4_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep IPADDR= | awk -F "IPADDR=" '{print \$2}'`;
}
else {
    $ipv4_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep IPADDR= | awk -F "IPADDR=" '{print \$2}'`;
}
chomp($ipv4_ip);

if ($System_new->{IPType}) {
    &debug_msg("Registered 'IPtype' change. Was: " . $System_old->{IPType} . " - New: " . $System_new->{IPType} . "\n");

    # Find all Vsites:
    my @vhosts = ();
    my (@vhosts) = $cce->findx('Vsite');

    if (($System->{IPType} eq "VZv6") || ($System->{IPType} eq "IPv6")) {
        &debug_msg("Switch to IPv6|VZv6 detected: Making sure all Vsites loose their IPv4 address and get an IPv6 address instead.\n");
        # Walk through all Vsites:
        for my $vsite (@vhosts) {
            ($ok, my $VsiteData) = $cce->get($vsite);
            if ($VsiteData->{ipaddr} ne '') {
                ($ok) = $cce->set($vsite, '', { 'ipaddr' => ''});
            }
            if ($VsiteData->{ipaddrIPv6} eq '') {
                ($ok) = $cce->set($vsite, '', { 'ipaddrIPv6' => $ipv6_ip });
            }
        }
        ($ok) = $cce->set($oids[0], '', { 'extra_ipaddr' => '' });
    }
    if (($System->{IPType} eq "VZv4") || ($System->{IPType} eq "IPv4")) {
        &debug_msg("Switch to IPv4|VZv4 detected: Making sure all Vsites loose their IPv6 address and get an IPv4 address instead.\n");
        # Walk through all Vsites:
        for my $vsite (@vhosts) {
            ($ok, my $VsiteData) = $cce->get($vsite);
            if ($VsiteData->{ipaddr} eq '') {
                ($ok) = $cce->set($vsite, '', { 'ipaddr' => $ipv4_ip });
            }
            if ($VsiteData->{ipaddrIPv6} ne '') {
                ($ok) = $cce->set($vsite, '', { 'ipaddrIPv6' => '' });
            }
        }
        ($ok) = $cce->set($oids[0], '', { 'extra_ipaddr_IPv6' => '' });
    }
}

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

# For debugging:
sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

# 
# Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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