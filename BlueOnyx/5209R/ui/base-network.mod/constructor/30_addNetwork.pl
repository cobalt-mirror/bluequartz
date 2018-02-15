#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: 30_addNetwork.pl
# Original author: jmayer@cobalt.com

# this needs use lib because it isn't a handler
use lib qw(/usr/sausalito/handlers/base/network);
use CCE;
use Network qw(find_eth_ifaces);
use Net::IP qw(:PROC);
use Data::Dumper;

$DEBUG = "0";
if ($DEBUG) {
        use Data::Dumper;
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $errors = 0;

my $cce = new CCE;
$cce->connectuds();

# get all the names of all current interfaces and aliases
my @devices = find_eth_ifaces();

&debug_msg("\@devices: " . Dumper (\@devices) . "\n");

# find_eth_ifaces failed if @devices is empty
if (!scalar(@devices))
{
    $cce->bye('FAIL', '[[base-network.noEthIfsFound]]');
    exit(1);
}

# Handle bootproto=dhcp on AWS, where we do NOT change ifcfg-eth0:
if (-f "/etc/is_aws") {
    $is_aws = "1";
}

my ($sysoid) = $cce->find('System');
my ($ok, $System) = $cce->get($sysoid);
if (!$ok) {
    &debug_msg("Running: No 'System' Object found, bailing.\n");
    $cce->bye('FAIL');
    exit 1;
}
my $IPType = $System->{IPType};

# for each inteface or alias
# get information from ifconfig
# make sure a Network object exists in CCE
# if necessary, destroy duplicates in CCE
for my $device (@devices) {
    &debug_msg("current device: $device\n");

    $net_device_cfg = $Network::NET_SCRIPTS_DIR . '/ifcfg-' . $device;

    my $onboot = '0';
    my $ipv4_ip = '';
    my $ipv4_nm = '';
    my $ipv6_ip = '';
    my $ipv6_secs_ip = '';
    my @ipv6_array = ();
    my @ipv6_array_out = ();

    if (-f $net_device_cfg) {
        &debug_msg("net_device_cfg: $net_device_cfg\n");

        # Interface enabled?
        $onboot = `LC_ALL=C cat $net_device_cfg | grep ^ONBOOT=yes | wc -l`;
        chomp($onboot);

        # Get IPv4 IP:
        $ipv4_ip = `LC_ALL=C cat $net_device_cfg | grep ^IPADDR= | awk -F "IPADDR=" '{print \$2}'`;
        chomp($ipv4_ip);

        # Get IPv4 Netmask:
        $ipv4_nm = `LC_ALL=C cat $net_device_cfg | grep ^NETMASK= | awk -F "NETMASK=" '{print \$2}'`;
        chomp($ipv4_nm);

        # Get IPv6 IP:
        $ipv6_ip = `LC_ALL=C cat $net_device_cfg | grep ^IPV6ADDR= | awk -F "IPV6ADDR=" '{print \$2}'`;
        chomp($ipv6_ip);

        &debug_msg("Running: My primary IPv4 ipaddr: " . $ipv4_ip . "\n");
        &debug_msg("Running: My primary IPv4 netmask: " . $ipv4_nm . "\n");
        &debug_msg("Running: My primary IPv6 ipaddr: " . $ipv6_ip . "\n");

        # Get IPv6 Secondaries:
        if (($device eq "eth0") || ($device eq "venet0")) {
            $ipv6_secs_ip = `LC_ALL=C cat $net_device_cfg| grep ^IPV6ADDR_SECONDARIES= | awk -F 'IPV6ADDR_SECONDARIES="' '{print \$2}'|awk -F '"' '{print \$1}'`;
            chomp($ipv6_secs_ip);
            &debug_msg("current ipv6_secs_ip: $ipv6_secs_ip\n");
            @ipv6_array = split ' ', $ipv6_secs_ip;
            foreach $ip_x (@ipv6_array) {
                # Remove Suffix and return IP in shorthand:
                $ip_test = Net::IP->new($ip_x);
                $ip = $ip_test->ip($ip_test);
                $ip_test = Net::IP->new($ip);
                $ip = $ip_test->short($ip_test);
                push @ipv6_array_out, $ip;
            }
            @ipv6_array_out = sort(@ipv6_array_out);
            &debug_msg("\@ipv6_array_out: " . Dumper (\@ipv6_array_out) . "\n");
            if (scalar(@ipv6_array_out)) {
                $extra_ipaddr_IPv6 = $cce->array_to_scalar(@ipv6_array_out);
                &debug_msg("IPv6 Scalar: $extra_ipaddr_IPv6\n");
                my ($SysOid) = $cce->find('System');
                if ($SysOid) {
                    # Get 'System':
                    my ($ok, $System) = $cce->get($SysOid);
                    if ($System->{extra_ipaddr_IPv6} ne $extra_ipaddr_IPv6) {
                        my ($ok) = $cce->update($SysOid, '', { 'extra_ipaddr_IPv6' => $extra_ipaddr_IPv6, 'nw_update' => time() });

                        # Force the write-out of the "IPV6ADDR_SECONDARIES" line to the primary Network-Interface config file.
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
                        my ($net_oid) = $cce->find('Network', { 'device' => $DEFAULT_INTERFACE, 'enabled' => 1, 'real' => 1 });
                        if ($net_oid) {
                            $cce->set($net_oid, '', { 'refresh' => time() });
                        }                        
                    }
                    else {
                        &debug_msg("No IPV6ADDR_SECONDARIES changes for $device\n");
                    }
                }
            }
        }

        $bootproto = 'none';
    }
    else {
        &debug_msg("net_device_cfg does not yet exist: $net_device_cfg\n");
    }

    # Get MAC:
    if (-f "/sys/class/net/$device/address") {
        my $mac = `cat /sys/class/net/$device/address`;
    }
    else {
        my $mac = '';
    }
    chomp($mac);

    my $bootproto = 'none';
    if ($is_aws == "1") {
        # If we're on AWS, set bootproto=dhcp:
        $bootproto = 'dhcp';
    }

    # If interface has IP and netmask (or IPv6 IP) set it to enabled:
    if (($ipv4_ip && $ipv4_nm) || ($ipv6_ip)) {
        $onboot = '1';
    }

    # Assemble Array for SET transaction:
    $new_obj->{enabled} = $onboot;
    $new_obj->{bootproto} = $bootproto;
    $new_obj->{mac} = $mac;
    $new_obj->{ipaddr} = $ipv4_ip;
    $new_obj->{netmask} = $ipv4_nm;
    $new_obj->{ipaddr_IPv6} = $ipv6_ip;
    $new_obj->{real} = '1';

    my @oids = $cce->find('Network', { 'device' => $device } );

    my $oid = 0;
    # Update existing info in CODB:
    if (scalar(@oids) == 1) {
        $oid = $oids[0];
        my ($ok, $obj) = $cce->get($oid);

        $DEBUG && print STDERR "onboot: $onboot - " . $obj->{enabled} .  "\n";
        $DEBUG && print STDERR "bootproto: $bootproto - " . $obj->{bootproto} .  "\n";
        $DEBUG && print STDERR "mac: $mac - " . $obj->{mac} .  "\n";
        $DEBUG && print STDERR "ipv4_ip: $ipv4_ip - " . $obj->{ipaddr} .  "\n";
        $DEBUG && print STDERR "ipv4_nm: $ipv4_nm - " . $obj->{netmask} .  "\n";
        $DEBUG && print STDERR "ipv6_ip: $ipv6_ip - " . $obj->{ipaddr_IPv6} .  "\n\n\n";

        if (($ipv4_ip ne $obj->{ipaddr}) || ($ipv4_nm ne $obj->{netmask}) || ($ipv6_ip ne $obj->{ipaddr_IPv6}) || ($onboot ne $obj->{enabled}) || ($mac ne $obj->{mac}) || ($bootproto ne $obj->{bootproto})) {
            &debug_msg("Something has changed. Need to update CODB for $device\n");
            my ($ok) = $cce->set($oid, '', $new_obj);
        }
        else {
            # No changes, no SET transaction.
            &debug_msg("No changes for $device\n");
        }
    } 
    elsif (scalar(@oids) == 0) {
        # no Network object for this device, so create one
        $new_obj->{device} = $device;

        my ($success) = $cce->create('Network', $new_obj);
        if (!$success) {
            &debug_msg("Failed to create Network object for $device\n");
            $errors++;
        } 
        else {
            &debug_msg("Created Network object for $device.\n");
        }
        # turn on NAT and IPForwarding
        hack_on_nat();
    } 
    else {
        # destroy extras
        shift(@oids);
        for my $network (@oids) {
            my ($success) = $cce->destroy($network);
            if ($success) {
                &debug_msg("Destroyed surplus Network.$device object $network\n");
            } 
            else {
                &debug_msg("Failed to destroy surplus Network.$device object $network\n");
                $errors++;
            }
        }
    }
}

$cce->bye();
exit($errors);

sub hack_on_nat {
    my ($oid) = $cce->find('System');
    if ($oid) {

        # Get 'System' / 'Network'
        my ($ok, $System_Network) = $cce->get($oid, 'Network');

        if (! -f "/proc/user_beancounters") {
            if (($System_Network->{nat} ne '1') || ($System_Network->{ipForwarding} ne '1')) {
                my ($ok) = $cce->update($oid, 'Network', { 'nat' => '1', 'ipForwarding' => '1' });
            }
        }
        else {
            if (($System_Network->{nat} ne '0') || ($System_Network->{ipForwarding} ne '0')) {
                my ($ok) = $cce->update($oid, 'Network', { 'nat' => '0', 'ipForwarding' => '0' });
            }
        }
    }
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
        print STDERR "$msg\n";
    }
}

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