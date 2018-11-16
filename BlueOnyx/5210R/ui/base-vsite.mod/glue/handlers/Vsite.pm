#!/usr/bin/perl -I/usr/sausalito/perl
# Vsite.pm: Functions for virtual hosting
#
# $Id: Vsite.pm
#
# Original authors:
#   Will DeHaan (will@cobaltnet.com)
#   Brion Moss (brion@cobaltnet.com, brion@nda.com)
#   Duncan Laurie <duncan@cobaltnet.com>
#   Harris Vaegan-Lloyd <harris@cobaltnet.com>
#
# Cannibalized by: 
#   Michael Stauber <mstauber@blueonyx.it>
# 

package Vsite;

require Exporter;

use vars qw(@ISA @EXPORT @EXPORT_OK);
use Net::IP qw(:PROC);
use Data::Dumper;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

@ISA    = qw[ Exporter ];
@EXPORT = qw[
        &vsite_disable &vsite_enable

        &vsite_get_xml 
        
        $Webdir
         $Sites_AdminGroup
        %DefaultAliases

        vsite_add_network_interface vsite_del_network_interface vsite_toggle_network_interface
    ];

use integer;
use Sauce::Config;
use File::Copy;
use Base::HomeDir qw(homedir_get_group_dir);

use vars qw(
        $DEFAULT_INTERFACE
        $Webdir
        $Sites_AdminGroup
        
        %DefaultAliases);

# globals used here
if ((-e "/proc/user_beancounters") && (-f "/etc/vz/conf/0.conf")) {
    $DEFAULT_INTERFACE = 'venet0';
}
elsif ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
    # No, we're in an OpenVZ VPS:
    $device = 'venet0';
}
else {
    $DEFAULT_INTERFACE = 'eth0';
}

# hard-coded configuration options
$Webdir = Sauce::Config::webdir();
$Sites_AdminGroup = 'site-adm';

# Mail
%DefaultAliases =     (
                    "MAILER-DAEMON",   1,
                    "sys",             1,
                    "nobody",          1,
                    "root",            1,
                    'apache',          1
                    );

### Removed;
#                    "postmaster",      1,
#                    'admin',           1,


sub vsite_disable
# disable a virtual site
#   1. disable all virtual site users
#   2. chmod 0000 site home directory
# arguments: vsite groupname
# return value: std
{
    my ($cce, $group) = @_;
    
    # make sure group isn't blank or we disable /home/sites below
    return 0 unless ($group);

    my @users = $cce->find('User', { 'site' => $group });
    
    foreach (@users)
    {
        $cce->set($_, '', { 'enabled' => 0 });    
    }
    
    my $mode = oct '0000';
    my $dir = homedir_get_group_dir($group);
    
    Sauce::Util::chmodfile($mode, $dir);
    
    return 1;
}

sub vsite_enable
# enable a virtual site
#   1. re-enable all virtual site users
#   2. chmod site home directory back to normal
# arguments: vsite groupname
# return value: std
{
    my ($cce, $group) = @_;

    return 0 unless ($group);

    my @users = $cce->find('User', { 'site' => $group });

    foreach (@users)
    {
        $cce->set($_, '', { 'enabled' => 1 });
    }

    my $mode = oct '2775';
    my $dir = homedir_get_group_dir($group);

    Sauce::Util::chmodfile($mode, $dir);

    return 1;
}

sub vsite_add_network_interface
{
    my $cce = shift;
    my $ipaddr = shift;
    my $user = shift;
    my $device = shift;

    $device ||= $DEFAULT_INTERFACE;

    # Get 'System' Object:
    my ($sysoid) = $cce->find('System');
    my ($ok, $System) = $cce->get($sysoid);
    if (!$ok) {
        $cce->bye('FAIL');
        exit 1;
    }

    # Check if IP is IPv4 or IPv6:
    $ip = Net::IP->new($ipaddr);
    $ip_check = $ip->version();

    &debug_msg("vsite_add_network_interface: Processing IP: $ipaddr - IP Protocol: $ip_check\n");

    # Get primary IPs:
    $ipv4_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep IPADDR= | awk -F "IPADDR=" '{print \$2}'`;
    chomp($ipv4_ip);    
    $ipv6_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-$device | grep IPV6ADDR= | awk -F "IPV6ADDR=" '{print \$2}'`;
    chomp($ipv6_ip);

    # Assemble empty IP arrays:
    my @extra_ipaddr = ();
    my @extra_ipaddr_IPv6 = ();

    # Populate then from CODB:
    @extra_ipaddr = $cce->scalar_to_array($System->{extra_ipaddr});
    @extra_ipaddr_IPv6 = $cce->scalar_to_array($System->{extra_ipaddr_IPv6});

    #
    ### Deal with IPv4:
    #

    if ($ip_check eq "4") {
        my ($net_if) = $cce->find('Network', { 'ipaddr' => $ipaddr, 'enabled' => 1 });
        if (not $net_if) {

            # Need to create a new network interface
            my ($eth) = $cce->find("Network", { 'device' => $device });
            (my $ok, $eth) = $cce->get($eth);

            for (my $alias = 0;; $alias++) {
                ($net_if) = $cce->find("Network", { 'device' => ($eth->{device} . ":$alias") });
                if (not $net_if) {
                    ($ok) = $cce->create("Network",
                        { 'device' => ($eth->{device} . ":$alias"),
                          'ipaddr' => $ipaddr,
                          'netmask' => $eth->{netmask},
                          'enabled' => 1,
                          'refresh' => time()
                        });

                    if (not $ok) {
                        $cce->bye('FAIL', '[[base-vsite.cantCreateNetwork]]');
                        exit(1);
                    }
                    &debug_msg("Running: /sbin/ip -4 route add $ipaddr dev $eth->{device}\n");
                    system("/sbin/ip -4 route add $ipaddr dev $eth->{device}");
                    last;
                }
            }
        }

        # Check if Vsite IP is already known to the server:
        if (in_array(\@extra_ipaddr, $ipaddr)) {
            # Yes, it is!
            return 1;
        }
        else {
            # No, it is not! Push it into the Array of IPs from CODB:
            push (@extra_ipaddr, $ipaddr);

            # Remove primary IP:
            @extra_ipaddr = grep {!/^$ipv4_ip$/} @extra_ipaddr;

            # Remove duplicates:
            my @filtered_ipv4 = uniq(@extra_ipaddr);

            # Sort IPs:
            @extra_ipaddr = sort {
                pack('C4' => $a =~
                  /(\d+)\.(\d+)\.(\d+)\.(\d+)/) 
                cmp 
                pack('C4' => $b =~
                  /(\d+)\.(\d+)\.(\d+)\.(\d+)/)
              } @filtered_ipv4;

            # Update 'System' Object with new IPs:
            $new_extra_ipaddr = $cce->array_to_scalar(@extra_ipaddr);
            ($ok) = $cce->set($sysoid, '', { 'extra_ipaddr' =>  $new_extra_ipaddr });
            if (not $ok) {
                $cce->bye('FAIL', '[[base-vsite.cantCreateExtraIPv4]]');
                exit(1);
            }
            return 1;
        }
        return 1;
    }

    #
    ### Deal with IPv6:
    #

    if ($ip_check eq "6") {
        # Check if Vsite IP is already known to the server:
        if (in_array(\@extra_ipaddr_IPv6, $ipaddr)) {
            &debug_msg("vsite_add_network_interface: IP: $ipaddr - is in Array \@extra_ipaddr_IPv6, returning early.\n");
            # Yes, it is!
            return 1;
        }
        else {
            # No, it is not! Push it into the Array of IPs from CODB:
            &debug_msg("vsite_add_network_interface: IP: $ipaddr - is *NOT* in Array \@extra_ipaddr_IPv6\n");
            push (@extra_ipaddr_IPv6, $ipaddr);

            # Remove primary IP:
            @extra_ipaddr_IPv6 = grep {!/^$ipv6_ip$/} @extra_ipaddr_IPv6;

            # Remove duplicates:
            my @filtered_ipv6 = uniq(@extra_ipaddr_IPv6);

            # Sort:
            @extra_ipaddr_IPv6 = sort @filtered_ipv6;
            # Check if each IPv6 extra IP is actually in use:
            foreach my $ip_extra (@extra_ipaddr_IPv6) {
                &debug_msg("Checking if Vsite uses $ip_extra\n");
                my @vsite_oids = $cce->find('Vsite', { 'ipaddrIPv6' => $ip_extra });
                if (scalar(@vsite_oids) == 0) {
                    # Not in use, remove element from array:
                    &debug_msg("Removing $ip_extra\n");
                    @filtered_ipv6 = grep {!/^$ip_extra$/} @extra_ipaddr_IPv6;
                }
            }
            # Sort:
            @extra_ipaddr_IPv6 = sort @filtered_ipv6;

            if (($System->{IPType} eq 'VZv6') || ($System->{IPType} eq 'VZBOTH')) {
                # Special case OpenVZ IPv6: All our IPs are always bound and don't change:
                $new_extra_ipaddr_IPv6 = $System->{extra_ipaddr_IPv6};
            }
            else {
                # Convert Array to Scalar and send it back into CODB:
                $new_extra_ipaddr_IPv6 = $cce->array_to_scalar(@extra_ipaddr_IPv6);
            }

            # Update 'System' Object with new IPs:
            &debug_msg("Vsite.pm vsite_add_network_interface: Updating 'System' with 'extra_ipaddr_IPv6':\n");
            ($ok) = $cce->set($sysoid, '', { 'extra_ipaddr_IPv6' =>  $new_extra_ipaddr_IPv6 });
            if (not $ok) {
                $cce->bye('FAIL', '[[base-vsite.cantCreateExtraIPv6]]');
                exit(1);
            }

            &debug_msg("In vsite_add_network_interface IPv6 Mechanism, DEFAULT_INTERFACE: $DEFAULT_INTERFACE\n");

            # Force the write-out of the "IPV6ADDR_SECONDARIES" line to the primary Network-Interface config file.
            my ($net_oid) = $cce->find('Network', { 'device' => $DEFAULT_INTERFACE, 'enabled' => 1, 'real' => 1 });
            if ($net_oid) {
                &debug_msg("In vsite_add_network_interface IPv6 Mechanism, net_oid: $net_oid\n");
                ($ok) = $cce->set($net_oid, '', { 'refresh' => time() });
                if (not $ok) {
                    $cce->bye('FAIL', '[[base-vsite.cantCreateExtraIPv6]]');
                    exit(1);
                }
                &debug_msg("Running: /sbin/ip -6 route add $ipaddr dev $DEFAULT_INTERFACE\n");
                system("/sbin/ip -6 route add $ipaddr dev $DEFAULT_INTERFACE");
            }
            return 1;
        }
    }
    return 1;
}

# this will bring down a Network interface that is no longer in use
# as long as it is an alias
sub vsite_del_network_interface
{
    my ($cce, $ipaddr) = @_;

    &debug_msg("vsite_del_network_interface: Processing IP: $ipaddr\n");

    # Check if IP is IPv4 or IPv6:
    $ip = Net::IP->new($ipaddr);
    $ip_check = $ip->version();

    &debug_msg("Running: vsite_del_network_interface: Checking if Vsites still use IP $ipaddr.\n");

    # Get 'System' Object:
    my ($sysoid) = $cce->find('System');
    my ($ok, $System) = $cce->get($sysoid);
    if (!$ok) {
        &debug_msg("Running: Could not find 'System' Object. Aborting.\n");
        $cce->bye('FAIL');
        exit 1;
    }

    # Assemble empty IP arrays:
    my @extra_ipaddr = ();
    my @extra_ipaddr_IPv6 = ();

    # Populate then from CODB:
    @extra_ipaddr = $cce->scalar_to_array($System->{extra_ipaddr});
    @extra_ipaddr_IPv6 = $cce->scalar_to_array($System->{extra_ipaddr_IPv6});

    &debug_msg("Running: IP address is an IPv" . $ip_check .  " address.\n");

    #
    ### Deal with IPv4:
    #

    if ($ip_check eq "4") {

        # only destroy the interface if not in use
        my @vsite_oids = $cce->find('Vsite', { 'ipaddr' => $ipaddr });
        if (scalar(@vsite_oids) == 0) {
            # We leave this in for legacy: destroy the interface, but only if it is not real
            my ($net_oid) = $cce->find('Network', { 'ipaddr' => $ipaddr, 'real' => 0 });
            if ($net_oid) {

                # Delete IPv4 Route while we are at it:
                &debug_msg("Running: /sbin/ip -4 route del $ipaddr\n");
                system("/sbin/ip -4 route del $ipaddr");

                # Destroy Network Alias Object:                
                $cce->set($net_oid, '', { 'enabled' => 0 });
                $cce->destroy($net_oid);
            }

            #
            ### Remove unusued 'extra_ip' from 'System' Object:
            #

            if (in_array(\@extra_ipaddr, $ipaddr)) {
                # Remove element from array:
                @extra_ipaddr = grep {!/^$ipaddr$/} @extra_ipaddr;
            }

            # Remove duplicates:
            my @filtered_ipv4 = uniq(@extra_ipaddr);

            # Sort IPs:
            @extra_ipaddr = sort {
                pack('C4' => $a =~ /(\d+)\.(\d+)\.(\d+)\.(\d+)/) 

                cmp

                pack('C4' => $b =~
                  /(\d+)\.(\d+)\.(\d+)\.(\d+)/)
              } @filtered_ipv4;

            # Convert Array to Scalar and send it back into CODB:
            $new_extra_ipaddr = $cce->array_to_scalar(@extra_ipaddr);
            #($ok) = $cce->set($sysoid, '', { 'extra_ipaddr' =>  $new_extra_ipaddr, 'nw_update' => time() });
            ($ok) = $cce->set($sysoid, '', { 'extra_ipaddr' =>  $new_extra_ipaddr });
            if (not $ok) {
                &debug_msg("Running: IP Fail: cantRemoveExtraIPv4\n");
                $cce->bye('FAIL', '[[base-vsite.cantRemoveExtraIPv4]]');
                exit(1);
            }
        }
        else {
            &debug_msg("Running: Vsites still seem to be using IP $ipaddr. Doing nothing. Debug: " . scalar(@vsite_oids) . "\n");
        }
    }

    #
    ### Deal with IPv6:
    #

    if ($ip_check eq "6") {

        $need_config_writeout = '0';

        # Check if there is a Vsite that still uses this IPv6 IP:
        my @vsite_oids = $cce->find('Vsite', { 'ipaddrIPv6' => $ipaddr });
        if (scalar(@vsite_oids) == 0) {
            # IP is no longer in use:
            $need_config_writeout++;
            if (in_array(\@extra_ipaddr_IPv6, $ipaddr)) {
                # Remove element from array:
                @extra_ipaddr_IPv6 = grep {!/^$ipaddr$/} @extra_ipaddr_IPv6;

                # Remove route for this IPv6 IP as well:
                &debug_msg("Running: /sbin/ip -6 route del $ipaddr\n");
                system("/sbin/ip -6 route del $ipaddr");                
            }
        }

        # Check if each *remaining* IPv6 extra IP is actually still in use:
        foreach my $ip_extra (@extra_ipaddr_IPv6) {
            &debug_msg("Checking if Vsite uses $ip_extra\n");
            my @vsite_oids = $cce->find('Vsite', { 'ipaddrIPv6' => $ip_extra });
            if (scalar(@vsite_oids) == 0) {
                # Not in use, remove element from array:
                $need_config_writeout++;
                &debug_msg("Removing $ip_extra\n");
                @filtered_ipv6 = grep {!/^$ip_extra$/} @extra_ipaddr_IPv6;
                # Remove routes for IPv6 IP's that are no longer in use:
                &debug_msg("Running: /sbin/ip -6 route del $ipaddr\n");
                system("/sbin/ip -6 route del $ipaddr");
            }
            # Remove duplicates:
            my @extra_ipaddr_IPv6 = uniq(@filtered_ipv6);
        }

        # Sort:
        @filtered_ipv6 = sort @extra_ipaddr_IPv6;


        if (($System->{IPType} eq 'VZv6') || ($System->{IPType} eq 'VZBOTH')) {
            # Special case OpenVZ IPv6: All our IPs are always bound and don't change:
            $new_extra_ipaddr_IPv6 = $System->{extra_ipaddr_IPv6};
        }
        else {
            # Convert Array to Scalar and send it back into CODB:
            $new_extra_ipaddr_IPv6 = $cce->array_to_scalar(@filtered_ipv6);
        }

        # Update 'System' Object with new IPs:
        &debug_msg("Vsite.pm vsite_del_network_interface: Updating 'System' with 'extra_ipaddr_IPv6':\n");
        ($ok) = $cce->set($sysoid, '', { 'extra_ipaddr_IPv6' =>  $new_extra_ipaddr_IPv6 });
        if (not $ok) {
            &debug_msg("Running: IP Fail: cantRemoveExtraIPv6\n");
            $cce->bye('FAIL', '[[base-vsite.cantRemoveExtraIPv6]]');
            exit(1);
        }

        # Force the write-out of the "IPV6ADDR_SECONDARIES" line to the primary Network-Interface config file.
        if ($need_config_writeout ne '0') {
            my ($net_oid) = $cce->find('Network', { 'device' => $DEFAULT_INTERFACE, 'enabled' => 1 });
            if ($net_oid) {
                $cce->set($net_oid, '', { 'refresh' => time() });
            }
        }
        else {
            &debug_msg("Running: Vsites still seem to be using IP $ipaddr.\n");
        }
        return 1;
    }
    return 1;
}

# Trigger Network Restart after IP changes:
sub vsite_toggle_network_interface {
    my $cce = shift;

    &debug_msg("Running: vsite_toggle_network_interface\n");

    # Get 'System' Object:
    my ($sysoid) = $cce->find('System');
    my ($ok, $System) = $cce->get($sysoid);
    if (!$ok) {
        &debug_msg("Running: Could not find 'System' Object. Aborting.\n");
        $cce->bye('FAIL');
        exit 1;
    }

    ($ok) = $cce->set($sysoid, '', { 'nw_update' => time() });
    if (not $ok) {
        $cce->bye('FAIL', '[[base-vsite.cantToggleNetwork]]');
        exit(1);
    }
    return 1;
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

1;

# 
# Copyright (c) 2015-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2018 Team BlueOnyx, BLUEONYX.IT
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