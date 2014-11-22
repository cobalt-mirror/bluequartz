#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: 30_addNetwork.pl
# Original author: jmayer@cobalt.com

# this needs use lib because it isn't a handler
use lib qw(/usr/sausalito/handlers/base/network);
use CCE;
use Network qw(find_eth_ifaces);

my $DEBUG = 0;

my $errors = 0;

my $cce = new CCE;
$cce->connectuds();

# get all the names of all current interfaces and aliases
my @devices = find_eth_ifaces();

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

# for each inteface or alias
# get information from ifconfig
# make sure a Network object exists in CCE
# if necessary, destroy duplicates in CCE
for my $device (@devices) 
{
    $DEBUG && print STDERR "current device: $device\n";

    # parse ifconfig
    my $ip = ''; 
    my $nm = '';
    my $mac = '';
    my $data = join('', `$Network::IFCONFIG $device`);
    if ($data =~ m/^$device/s) 
    {

        # Format EL5/EL6:
        # venet0:0  Link encap:UNSPEC  HWaddr 00-00-00-00-00-00-00-00-00-00-00-00-00-00-00-00  
        #           inet addr:38.114.102.15  P-t-P:38.114.102.15  Bcast:38.114.102.15  Mask:255.255.255.255
        #           UP BROADCAST POINTOPOINT RUNNING NOARP  MTU:1500  Metric:1
        #
        # Format EL7:
        # venet0:0: flags=211<UP,BROADCAST,POINTOPOINT,RUNNING,NOARP>  mtu 1500
        #         inet 38.114.102.16  netmask 255.255.255.255  broadcast 38.114.102.16  destination 38.114.102.16
        #         unspec 00-00-00-00-00-00-00-00-00-00-00-00-00-00-00-00  txqueuelen 0  (UNSPEC)

        if (($data =~ m/inet addr:\s*(\S+)/s) || ($data =~ m/inet \s*(\S+)/s))
        {
            $ip = $1;
            $DEBUG && print STDERR "IP: $ip\n";
        }
        if (($data =~ m/Mask:\s*(\S+)/s) || ($data =~ m/netmask\s*(\S+)/s))
        {
            $nm = $1;
            $DEBUG && print STDERR "Mask: $nm\n";
        }
        if (($data =~ m/HWaddr\s*(\S+)/s) || ($data =~ m/unspec\s*(\S+)\s*txqueuelen/s))
        {
            $mac = $1;
            $DEBUG && print STDERR "MAC: $mac\n";
        }
    }

    my @oids = $cce->find('Network', { 'device' => $device } );

    my $oid = 0;
    if (scalar(@oids) == 1) 
    {
        $oid = $oids[0];

        # Update info in CODB:
        if ($ip && $nm) 
        {
            $obj->{ipaddr} = $ip;
            $obj->{netmask} = $nm;
            $obj->{mac} = $mac;
            $obj->{enabled} = 1;

            # If we're on AWS, set bootproto=dhcp:
            if ($is_aws == "1") {
                $obj->{bootproto} = 'dhcp';
            }

            $DEBUG && print STDERR "Updating config: $ip/$nm in OID $oid\n";
            my ($ok) = $cce->set($oid, '', $obj);
        } 

    } 
    elsif (scalar(@oids) == 0) 
    {
        # no Network object for this device, so create one
        my $obj = { 'device' => $device };
        if ($device !~ /:\d+$/)
        {
            # this is a real interface
            $obj->{real} = 1;
        }

        # if according to ifconfig this interface has an address and
        # netmask, set those and mark it as enabled
        if ($ip && $nm) 
        {
            $DEBUG && print STDERR "Using old config: $ip/$nm\n";
            $obj->{ipaddr} = $ip;
            $obj->{netmask} = $nm;
            $obj->{mac} = $mac;
            $obj->{enabled} = 1;

            # If we're on AWS, set bootproto=dhcp:
            if ($is_aws == "1") {
                $obj->{bootproto} = 'dhcp';
            }

        } 
        else 
        {
            # check the config file to see if this interface should
            # be enabled on boot
            $obj->{enabled} = &onboot($device);
        }

        my ($success) = $cce->create('Network', $obj);
        if (!$success) 
        {
            $DEBUG && print STDERR "Failed to create Network object for $device\n";
            $errors++;
        } 
        else 
        {
            $DEBUG && print STDERR "Created Network object for $device.\n";
        }
        # turn on NAT and IPForwarding
        hack_on_nat();

        # update MAC address
        # don't update mac address
        # $oid = $cce->oid();
    } 
    else 
    {
        # destroy extras
        shift(@oids);
        for my $network (@oids) 
        {
            my ($success) = $cce->destroy($network);
            if ($success) 
            {
                $DEBUG && print STDERR "Destroyed surplus Network.$device object $network\n";
            } 
            else 
            {
                $DEBUG && print STDERR "Failed to destroy surplus Network.$device object $network\n";
                $errors++;
            }
        }
    }

    # make sure the real flag is properly set
    if (! -f "/proc/user_beancounters") { 
    # Handle standard ethX setups:
        if ($oid && $device !~ /:\d+$/) {
        $cce->set($oid, '', { 'real' => 1 });
        }
        elsif ($oid) {
            $cce->set($oid, '', { 'real' => 0 });
        }
    }
    else {
    # Handle OpenVZ cases where venet0:0 is the (first) real interface
    if ($oid && $device =~ /:0/) {
        $cce->set($oid, '', { 'real' => 1 });
        }
        elsif ($oid) {
            $cce->set($oid, '', { 'real' => 0 });
        }
    $cce->set($oid, '', { 'mac' => $mac });
    }
}

$cce->bye();
exit($errors);

sub hack_on_nat {
    my ($oid) = $cce->find('System');
    if ($oid) {
    if (! -f "/proc/user_beancounters") {
            my ($ok) = $cce->set($oid, 'Network', { 
                            'nat' => '1',
                            'ipForwarding' => '1',
                        });
    }
    else {
            my ($ok) = $cce->set($oid, 'Network', { 
                            'nat' => '0',
                            'ipForwarding' => '0',
                        });
    }
        if (not $ok) {
            $cce->warn('[[base-network.cantTurnOnNat]]');
        }

        # debugging:
        if ($DEBUG) {
            system('/bin/cp',
                '/etc/sysconfig/network',
                '/tmp/.network.' . scalar(time()) );
        }
    }
}
    
# check if ifcfg has onboot flag set to yes
# if so returns 1, else returns 0
sub onboot
{
    my $device = shift;

    my $onboot = 0;

    if(open(IFCFG, "$Network::NET_SCRIPTS_DIR/ifcfg-$device")) 
    {
        while(<IFCFG>) 
        {
            if(/^ONBOOT=(\w+)$/i) 
            {
                if ($1 =~ /yes/i) 
                {
                    $onboot = 1;
                }
            }
        }

        close IFCFG;
    }

    return $onboot;
}

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