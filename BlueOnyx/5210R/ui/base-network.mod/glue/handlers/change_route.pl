#!/usr/bin/perl -I/usr/sausalito/perl -I.
#
# $Id: change_route.pl
#
# Keeps the routing table on a running machine up to date with
# as little disturbance as possible.
#
# This script:
#
#   1) If we're on AWS, we do nothing.
#   2) If $System->{nw_update} doesn't have a recent timestamp it does nothing.
#   3) If IPv6 is disabled (no IPv6 Gateway) it will disable IPv6.
#   4) Binds all IPv4 extra IPs to eth0 and creates proper routes for them.
#   5) Creates IPv4 loopback route (IPv6 lo route is created automatically!)
#   6) Binds all IPv6 extra IPs to eth0. Routes are created automatically.
#   7) OpenVZ provisions present to handle GATEWAY/GATEWAYDEV config.
#
# routes are gathered from:
#   network interfaces (/sbin/ip)
#   the system default gateway
#
# Note: the "-c" option can be used to run change_route.pl as a standalone
# command-line tool, rather than a handler.
#
# Additional note: The extra_ips for eth0 *must* be stored in the 'System'
# Object. Because if this runs as a handler, it cannot use $cce->get() in
# Handler context to fetch the 'Network' object of eth0 that would contain
# the info in a perfect world. 
#

use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/network);

# Debugging switch:
$DEBUG = "1";
if ($DEBUG) {
        use Data::Dumper;
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

use FileHandle;
use Sauce::Config;
use Sauce::Util;
use Sauce::Service;
use CCE;
use Getopt::Long;
use Network qw(find_eth_ifaces);

my $CMDLINE = 0;
GetOptions('cmdline', \$CMDLINE, 'debug', \$DEBUG);

&debug_msg("Running $0: starting\n");
$DEBUG && print STDERR "$0: starting.\n";

my $cce = new CCE(Domain => 'base-network');

if ($CMDLINE) {
    $cce->connectuds();
} 
else {
    $cce->connectfd();
}

# Handle bootproto=dhcp on AWS, where we do NOT change ifcfg-eth0:
if (-f "/etc/is_aws") {
        $cce->bye('SUCCESS');
        exit(0);
}

# Are we an OpenVZ mastern node?
if ((-e "/proc/user_beancounters") && (-f "/etc/vz/conf/0.conf")) {
    # Yes, we are.
    $device = 'venet0:0';
}
else {
    # No, we are not.
    $device = 'eth0';
}

# Special case: IPv6 default route:
my $gateway_IPv6 = '';
my ($sysoid) = $cce->find('System');
&debug_msg("Running $0: My sysoid: " . $sysoid . "\n");
my ($ok, $System) = $cce->get($sysoid);
if (!$ok) {
    &debug_msg("Running $0: No 'System' Object found, bailing.\n");
    $cce->bye('FAIL');
    exit 1;
}
else {
    $gateway_IPv6 = $System->{gateway_IPv6};
    $nw_update = $System->{nw_update};
}

if (($nw_update eq '0') || ($nw_update eq '')) {
    &debug_msg("Running $0: Early exit to not restart network just yet.\n");
    $cce->bye('SUCCESS');
    exit(0);
}

# Check if a recent network update mandates a restart of the Network:
$now = time();
$time_window = $nw_update + '60';
&debug_msg("Running $0: now: $now - time_window: $time_window.\n");
if ($time_window gt $now) {
    &debug_msg("Running $0: Flushing IPv6 and bringing up $device\n");
    # Soft Restart Network:
    system("/sbin/ip -6 addr flush dev $device");
    #system("/bin/sleep 3");
    #Sauce::Service::service_run_init('network', 'restart');
    system("/usr/bin/flock -n /usr/sausalito/license/change_route.lock /sbin/ifup $device");
}

if ($gateway_IPv6 eq "") {
    &debug_msg("Running $0: Flushing IPv6 routes.\n");
    system("/sbin/ip -6 addr flush dev $device");
    system("/sbin/ip -6 route del default");
}
else {
    # Get existing IPv6 default route:
    @ipv6_default_routes = split (/\n/, `LC_ALL=C /sbin/ip -6 route show default|awk -F "default via " '{print \$2}'|awk -F " dev" '{print \$1}'`);
    if (in_array(\@ipv6_default_routes, $gateway_IPv6)) {
        # IPv6 default route already present.
    }
    else {
        &debug_msg("Running $0: Adding default IPv6 route.\n");
        system("/sbin/ip -6 route add default via $gateway_IPv6");
    }
}

#
### Handle Assignment of Extra IPs to $device:
#

# Get IP addresses currently bound to $device and also the existing routes:
@arr_assigned_ipv4 = split (/\n/, `LC_ALL=C /sbin/ip address show dev $device |grep inet|grep global|awk -F "inet " '{print \$2}'|awk -F " brd " '{print \$1}'|cut -d / -f1|sed '/^\$/d'`);
@arr_assigned_ipv6 = split (/\n/, `LC_ALL=C /sbin/ip address show dev $device |grep inet|grep global|awk -F "inet6 " '{print \$2}'|awk -F " brd " '{print \$1}'|cut -d / -f1|sed '/^\$/d'`);
@routes_existing_ipv4 = split (/\n/, `LC_ALL=C /sbin/ip route show dev $device|grep -v default|awk -F "/" '{print \$1}'|awk -F " scope link" '{print \$1}'`);

# Get primary IPs of '$device' from Network Config file:
$ipv4_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep IPADDR= | awk -F "IPADDR=" '{print \$2}'`;
chomp($ipv4_ip);
&debug_msg("Running $0: My primary IPv4 ipaddr: " . $ipv4_ip . "\n");
$ipv4_nm = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep NETMASK= | awk -F "NETMASK=" '{print \$2}'`;
chomp($ipv4_nm);
&debug_msg("Running $0: My primary IPv4 netmask: " . $ipv4_nm . "\n");
$ipv6_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep IPV6ADDR= | awk -F "IPV6ADDR=" '{print \$2}'`;
chomp($ipv6_ip);
&debug_msg("Running $0: My primary IPv6 ipaddr: " . $ipv6_ip . "\n");

# Remove primary IPv4 and IPv6 IPs from our arrays:
#@arr_assigned_ipv4 = grep {!/^$ipv4_ip$/} @arr_assigned_ipv4;
@arr_assigned_ipv6 = grep {!/^$ipv6_ip$/} @arr_assigned_ipv6;
@arr_existing_netroutes = ();

#
## Assign IPv4 IPs that are not yet bound to $device:
#

# Make sure all IPv4 extra-IPs are bound:
if ($System->{extra_ipaddr}) {
    @extra_ipaddr = $cce->scalar_to_array($System->{extra_ipaddr});
    push (@extra_ipaddr, $ipv4_ip);
    foreach my $ip_extra (@extra_ipaddr) {
        &debug_msg("Running $0: Found extra IPv4 IP: $ip_extra\n");
        if (in_array(\@arr_assigned_ipv4, $ip_extra)) {
            # Remove element from array:
            @arr_assigned_ipv4 = grep {!/^$ip_extra$/} @arr_assigned_ipv4;
        }
        else {
            &debug_msg("Running $0: Extra IPv4 IP $ip_extra needs to be bound.\n");
            system("/sbin/ip addr add " . $ip_extra. "/32 dev $device");
            # Remove element from array:
            @arr_assigned_ipv4 = grep {!/^$ip_extra$/} @arr_assigned_ipv4;
        }

        # Handle netroutes (just keep track of them here, process later):
        my $netroute = ip_and_ip($ip_extra, $ipv4_nm);
        if (in_array(\@unique_netroutes, $netroute)) {
            # Nada
        }
        else {
            push (@unique_netroutes, $netroute);
        }

        # Handle regular IPv4 routes:
        if (in_array(\@routes_existing_ipv4, $ip_extra)) {
            # ip_extra route exists, skipping
            @routes_existing_ipv4 = grep {!/^$ip_extra$/} @routes_existing_ipv4;
        }
        else {
            system("/sbin/ip route add " . $ip_extra. "/255.255.255.255 dev $device");
            @routes_existing_ipv4 = grep {!/^$ip_extra$/} @routes_existing_ipv4;
        }
    }
}

# Set up netroutes:
foreach my $netroute (@unique_netroutes) {
    if (in_array(\@routes_existing_ipv4, $netroute)) {
        # Nada
    }
    else {
        system("/sbin/ip route add " . $netroute . "/" . $ipv4_nm . " dev $device");
    }
    @arr_assigned_ipv4 = grep {!/^$netroute$/} @arr_assigned_ipv4;
    @routes_existing_ipv4 = grep {!/^$netroute$/} @routes_existing_ipv4;    
}

# Unbind any extra IPv4 IP that is still bound, but not known to the GUI:
foreach my $ip_extra (@arr_assigned_ipv4) {
    &debug_msg("Running $0: Unbinding Extra IPv4 IP $ip_extra.\n");
    system("/sbin/ip addr del " . $ip_extra. "/32 dev $device");
}

# Removing routes for that we no longer have IPs:
foreach my $ip_extra (@routes_existing_ipv4) {
    &debug_msg("Running $0: Removing route for $ip_extra.\n");
    system("/sbin/ip route del " . $ip_extra . " dev $device");
}

# Handle the the IPv4 loopback route:
my $loop_ipv4 = `LC_ALL=C /sbin/ip route show dev lo|grep "127.0.0.0/8 scope link"|wc -l`;
chomp($loop_ipv4);
if ($loop_ipv4 ne "1") {
    # No route present. Add one:
    system("/sbin/ip route add " . '127.0.0.0/255.0.0.0' . " dev lo");
}

#
## Assign IPv6 IPs that are not yet bound to $device:
#

# Make sure all IPv6 extra-IPs are bound:
if ($System->{extra_ipaddr_IPv6}) {
    @extra_ipaddr = $cce->scalar_to_array($System->{extra_ipaddr_IPv6});
    #push (@extra_ipaddr, $ipv6_ip);
    foreach my $ip_extra (@extra_ipaddr) {
        &debug_msg("Running $0: Found extra IPv6 IP: $ip_extra\n");
        if (in_array(\@arr_assigned_ipv6, $ip_extra)) {
            # Remove element from array:
            @arr_assigned_ipv6 = grep {!/^$ip_extra$/} @arr_assigned_ipv6;
        }
        else {
            &debug_msg("Running $0: Extra IPv6 IP $ip_extra needs to be bound.\n");
            system("/sbin/ip addr add " . $ip_extra. "/128 dev $device");
            # Remove element from array:
            @arr_assigned_ipv6 = grep {!/^$ip_extra$/} @arr_assigned_ipv6;
        }
    }
}

# Unbind any extra IPv6 IP that is still bound, but not known to the GUI:
foreach my $ip_extra (@arr_assigned_ipv6) {
    &debug_msg("Running $0: Unbinding Extra IPv6 IP $ip_extra.\n");
    system("/sbin/ip addr del " . $ip_extra. "/128 dev $device");
}

######################

# Handle OpenVZ network situation:
if ((-e "/proc/user_beancounters") && (! -f "/etc/vz/conf/0.conf")) {

    # This is a bit special. Some time ago OpenVZ switched the network behaviour of VPS's
    # a little. In the past the fake GATEWAY of 192.0.2.1" was mandatory. Now it is no
    # longer mandatory. But if it is missing, then we at least need to have the parameter
    # GATEWAYDEV set to "venent0" in /etc/sysconfig/network. This routine here parses
    # /etc/sysconfig/network if we're under OpenVZ. It checks if GATEWAY and/or GATEWAYDEV
    # are set. If one of them is not set, we check if we have a network connection by
    # pinging the IP "8.8.8.8". If we get no result, we edit /etc/sysconfig/network and
    # make sure that both the fake GATEWAY and the GATEWAYDEV are set. Finally the
    # network is restarted.

    # Parse /etc/sysconfig/network:
    $sys_network = "/etc/sysconfig/network";
    if (-f $sys_network) {
    open (F, "/etc/sysconfig/network") || die "Could not open $sys_network $!";
    while ($line = <F>) {
            chomp($line);
            next if $line =~ /^\s*$/;               # skip blank lines
            next if $line =~ /^#$/;                 # skip comments
            if ($line =~ /^GATEWAY=(.*)$/) {
                $my_gateway = $1;
                if ($my_gateway =~ /^\"(.*)\"/g) {
                    $my_gateway = $1;
                }
                
            }
            if ($line =~ /^GATEWAYDEV=(.*)$/) {
                $my_gatewaydev = $1;
                if ($my_gatewaydev =~ /^\"(.*)\"/g) {
                    $my_gatewaydev = $1;
                }
            }
    }
    close(F);
    }

    # We're on OpenVZ. See if we have either GATEWAY or GATEWAYDEV defined:
    if ((!$my_gateway) || (!$my_gatewaydev)) {
        # At least either GATEWAY or GATEWAYDEV are undefined. Test network connectivity
        # to see if we can establish a network connection to the outside:
        use Net::Ping;
        $p = Net::Ping->new();
        $host = "8.8.8.8";
        if (!$p->ping($host)) {
            # Network is dead. We need to fix it.

            # Build output hash:
            if ((!$my_gateway) && (!$my_gatewaydev)) {
                $server_sys_network_writeoff = {
                    'GATEWAY' => '"192.0.2.1"',
                    'GATEWAYDEV' => '"venet0"'
                };
            }
            elsif ((!$my_gateway) && ($my_gatewaydev)) {
                $server_sys_network_writeoff = {
                    'GATEWAY' => '"192.0.2.1"'
                };
            }
            else {
                $server_sys_network_writeoff = {
                    'GATEWAYDEV' => '"venet0"'
                };
            }

            # Edit /etc/sysconfig/network:
            &edit_sys_network;

            # Restart Network:
            Sauce::Service::service_run_init('network', 'restart');
        }
        $p->close();
    }
}

$cce->bye('SUCCESS');
exit(0);

#
### Subroutines:
#

sub ip_and_ip
{
    my ($ip1, $ip2) = (shift, shift);
    my ($bip1,$bip2) = (ip2bin($ip1), ip2bin($ip2));
    return bin2ip($bip1 & $bip2);
}

# convert an ip address from its binary representation
# to its dotted-quad representation
sub bin2ip
{
    return join('.', unpack('C4', pack('N', shift)));
}

# convert an ip address from dotted-quad to binary
sub ip2bin
{
    return unpack('N', pack('C4', split(/\./, shift)));
}

sub which_device
{
    my ($ip, @nets) = @_;
    foreach my $net (@nets) 
    {
        if (is_ip_in_net($ip, $net->{ipaddr}, $net->{netmask})) 
        {
            return ($net->{device});
        }
    }
    if ((-e "/proc/user_beancounters") && (! -f "/etc/vz/conf/0.conf")) {
        $GuessDevice = 'venet0';
        return $GuessDevice;
    }
    else {
        $GuessDevice = 'eth0';
        return $GuessDevice;
    }
    #return '';
}

sub is_ip_in_net
{
    my ($ip, $network, $netmask) = @_;
    my ($bip, $bnet, $bmask) = (ip2bin($ip), ip2bin($network), ip2bin($netmask));
    return (($bip & $bmask) == ($bnet & $bmask));  
}

sub edit_sys_network {

    $ok = Sauce::Util::editfile(
        $sys_network,
        *Sauce::Util::hash_edit_function,
        '\n',
        { 're' => ' = ', 'val' => '=' },
        $server_sys_network_writeoff);

    # Error handling:
    unless ($ok) {
        $cce->bye('FAIL', "Error while editing $sys_network!");
        exit(1); 
    }
    system("/bin/rm -f /etc/sysconfig/network.backup.*");
}

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

sub uniq {
    my %seen;
    grep !$seen{$_}++, @_;
}

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
# Copyright (c) 2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
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