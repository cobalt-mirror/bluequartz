#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/network
#
# $Id: rewrite-ifcfg.pl
#
# rewrite the ifcfg file for the interface being modified with the new settings
#

use CCE;
use Sauce::Util;
use Network;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old_net = $cce->event_old();

my $device = $obj->{'device'};
my $ipaddr = $obj->{'enabled'} ? $obj->{'ipaddr'} : '0.0.0.0';
my $ipaddr_IPv6 = $obj->{'enabled'} ? $obj->{'ipaddr_IPv6'} : '';
my $netmask = $obj->{'netmask'};

#my $enabled = $obj->{'enabled'} && $ipaddr;
my $enabled = $obj->{'enabled'};
if (($obj->{'ipaddr'} ne "") || ($obj->{'ipaddr_IPv6'} ne "")) {
    $enabled = '1';
}

my $bootproto = $obj->{'bootproto'};
my ($network, $broadcast) = calcnetwork($ipaddr, $netmask);
my $onboot = $enabled ? 'yes' : 'no';

my $gateway = '';
my $gateway_IPv6 = '';
my $IPType = 'IPv4';
my ($sysoid) = $cce->find('System');
my ($ok, $System) = $cce->get($sysoid);
if (!$ok) {
    $cce->bye('FAIL');
    exit 1;
}
else {
    $gateway = $System->{gateway};
    $gateway_IPv6 = $System->{gateway_IPv6};
    $IPType = $System->{IPType};
}

&debug_msg("Running rewrite-ifcfg.pl\n");

# If the device is 'venet0', then we must NOT change it, as the network scripts are handled by OpenVZ:
if ($device =~ /venet0/) {
        $cce->bye('SUCCESS');
        exit(0);
}

# Handle bootproto=dhcp on AWS, where we do NOT change ifcfg-eth0:
if (-f "/etc/is_aws") {
    $is_aws = "1";
}

# this checks ipaddr here, because ipaddr is not optional
# and we need at least an IPv4 or IPv6 IP before we think
# about writing an ifcfg file. 
# this handler doesn't run on Network._DESTROY
if ((not $obj->{ipaddr}) && (not $obj->{ipaddr_IPv6})) {
        #$cce->bye('SUCCESS');
        #exit(0);
}

# edit ifcfg file
my $filename = $Network::NET_SCRIPTS_DIR . '/ifcfg-' . $device;
my $ok = Sauce::Util::editfile($filename, *edit_ifcfg, $filename);

# bring down the old device first if it's changing, otherwise
# bringing up the new device will fail if the ip is the same
if (!$cce->event_is_create() && $new->{device})
{
    # bring down the old device if necessary
    if ($old_net->{enabled})
    {
        my @down_cmd = ($Network::IFCONFIG, $old_net->{device}, 'down');
        system(@down_cmd);

        # rollback for old device taken care of by next addrollbackcommand
    }
}

# check if there is an alias to bring down before changing this interface
if ($enabled && $obj->{real} && $new->{ipaddr}) {
    my ($alias_oid) = $cce->find('Network',
                     { 
                        'real' => 0,
                        'enabled' => 1,
                        'ipaddr' => $new->{ipaddr} 
                     });
    if ($alias_oid) {
        my ($ok, $alias) = $cce->get($alias_oid);

        #
        # need to bring down the alias interface so that the IP
        # can be assigned to the real interface being modified
        # this is okay, because the alias object will end up being
        # destroyed by the cleanup_aliase.pl handler anyways
        #
        my @cmd = ($Network::IFCONFIG, $alias->{device}, 'down');
        my @rollback_cmd = ($Network::IFCONFIG, $alias->{device});
        push @rollback_cmd, $alias->{ipaddr};
        my $a_broadcast = (calcnetwork($alias->{ipaddr}, $alias->{netmask}))[1];
        push @rollback_cmd, 'broadcast', $a_broadcast;
        push @rollback_cmd, 'netmask', $alias->{netmask}, 'up';

        # ifconfig alias down
        system(@cmd);
        $DEBUG && warn(join(' ', @cmd));
        Sauce::Util::addrollbackcommand(@rollback_cmd);
    }
}

# modify the running interface
my @cmd = ($Network::IFCONFIG, $device); 
if ($enabled)
{
    push @cmd, $ipaddr, 'broadcast', $broadcast, 'netmask', $netmask;
}
else
{
    push @cmd, '0.0.0.0';
}
push @cmd, ($enabled ? 'up' : 'down');
$DEBUG && warn('modify: ' . join(' ', @cmd));
system(@cmd);

# add rollback command, in case there is a failure somewhere else
my @rollback_cmd = ($Network::IFCONFIG);
push @rollback_cmd, ($old_net->{device} ? $old_net->{device} : $device);
if ($old_net->{ipaddr} && ($old_net->{ipaddr} ne '0.0.0.0'))
{
    my ($junk, $bcast) = calcnetwork($old_net->{ipaddr}, $old_net->{netmask});
    push @rollback_cmd, $old_net->{ipaddr}, 'broadcast', $bcast, 'netmask', $old_net->{netmask};
}
push @rollback_cmd, ($old_net->{enabled} ? 'up' : 'down');
$DEBUG && warn('rollback: ' . join(' ', @rollback_cmd));
Sauce::Util::addrollbackcommand(@rollback_cmd);

# handle changing the device name
if (!$cce->event_is_create() && $new->{device})
{
    # add rollback command to bring down new device if necessary
    # the new device must go down before the old device is brought back
    # up if the ip is the same
    if ($enabled)
    {
        @rollback_cmd = ($Network::IFCONFIG, $new->{device}, 'down');
        Sauce::Util::addrollbackcommand(@rollback_cmd);
    }
    
    # clean up old ifcfg file
    my $old_filename = $Network::NET_SCRIPTS_DIR . '/ifcfg-' 
                        . $old_net->{device};
    Sauce::Util::unlinkfile($old_filename);
}

# assure correct permissions on file
Sauce::Util::chmodfile(0755, $filename);

$cce->bye('SUCCESS');
exit(0);

####################### helper functions ##############################

# calculate the network to which the specified ip address belongs
sub calcnetwork
{
    my ($ipaddr, $netmask) = (shift, shift);

    # convert the ip address and netmask to binary representations
    my $binip = pack('CCCC', split(/\./, $ipaddr));
    my $binmask = pack('CCCC', split(/\./, $netmask));

    # calculate the network
    my $binnet = $binip & $binmask;

    # calculate the broadcast address
    my $binbcast = $binnet | ~$binmask;
    
    # convert network and broadcast into dotted-quad format
    my $network = join('.', unpack('CCCC', $binnet));
    my $bcast   = join('.', unpack('CCCC', $binbcast));
    
    return ($network, $bcast);
}

sub edit_ifcfg {

    &debug_msg("Running rewrite-ifcfg.pl (inside 'edit_ifcfg'): IPType: $IPType - device: $device - gw: $gateway - gw6: $gateway_IPv6\n"); 

    my ($fin, $fout, $filename) = @_;
    
    if ($is_aws == "1") {
        print $fout "# $filename" . "\n";
        print $fout "# generated by $0" . "\n";
        print $fout "#" . "\n";
        print $fout "# Network settings for this interface are obtained via DHCP!" . "\n";
        print $fout "#" . "\n";
        print $fout "# ${oid}.device = $device" . "\n";
        print $fout "# ${oid}.bootproto = $bootproto" . "\n";
        print $fout "DEVICE=$device" . "\n";
        print $fout "BOOTPROTO=$bootproto" . "\n";
        print $fout "ONBOOT=$onboot" . "\n";
        print $fout "USERCTL=no" . "\n";
        return 1;
    }
    else {
        print $fout "# $filename" . "\n";
        print $fout "# generated by $0" . "\n";
        print $fout "#" . "\n";
        print $fout "# ${oid}.device = $device" . "\n";
        if (($ipaddr ne '') && ($gateway ne '') && ($netmask ne '')) {
            print $fout "# ${oid}.ipaddr = $ipaddr" . "\n";
            print $fout "# ${oid}.netmask = $netmask" . "\n";
        }
        if (($ipaddr_IPv6 ne '') && ($gateway_IPv6 ne '')) {
            print $fout "# ${oid}.ipaddr_IPv6 = $ipaddr_IPv6" . "\n";
            print $fout "# ${oid}.gateway_IPv6 = $gateway_IPv6" . "\n";
        }
        print $fout "#" . "\n";
        print $fout "DEVICE=$device" . "\n";
        print $fout "BOOTPROTO=$bootproto" . "\n";
        print $fout "ONBOOT=$onboot" . "\n";
        print $fout "DELAY=0" . "\n";
        print $fout "NM_CONTROLLED=no" . "\n";
        if (($ipaddr ne '') && ($gateway ne '') && ($netmask ne '')) {
            print $fout "BROADCAST=$broadcast" . "\n";
            print $fout "NETWORK=$network" . "\n";
            print $fout "NETMASK=$netmask" . "\n";
            print $fout "IPADDR=$ipaddr" . "\n";
        }
        print $fout "USERCTL=no" . "\n";
        print $fout "ARPCHECK=no" . "\n";

        if (($IPType eq 'IPv6') || ($IPType eq 'VZv6') || ($IPType eq 'BOTH') || ($IPType eq 'VZBOTH')) {
            print $fout "IPV6INIT=yes" . "\n";
            if (($IPType eq 'IPv6') || ($IPType eq 'BOTH')) {
                # Note: We do NOT need these on OpenVZ VPS's. Primary IP there is the 
                # first extra-IP and we use venet0 as Gateway instead of IPV6_DEFAULTGW.
                print $fout "IPV6ADDR=$ipaddr_IPv6" . "\n";
                print $fout "IPV6_DEFAULTGW=$gateway_IPv6" . "\n";
            }
            if (($device eq "eth0") || ($device eq "venet0")) {
                # Are we an OpenVZ master-node?
                if ((-e "/proc/user_beancounters") && (-f "/etc/vz/conf/0.conf")) {
                    # Yes, we are. We are NOT adding the below to either 'eth0' or 'venet0' on master nodes.
                    return 1;
                }
                elsif ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
                    # No, we're in an OpenVZ VPS, so we stop here, because we may not edit our network config files.
                    return 1;
                }
                else {
                    # No, we are not. Go on then:
                    if ($System->{extra_ipaddr_IPv6}) {
                        @extra_ipaddr_out = ();
                        @extra_ipaddr = $cce->scalar_to_array($System->{extra_ipaddr_IPv6});
                        # Sort:
                        @sorted_extra_ipaddr = sort @extra_ipaddr;
                        # Remove doublettes:
                        @extra_ipaddr = uniq(@sorted_extra_ipaddr);
                        foreach my $ip_extra (@extra_ipaddr) {
                            $ipv6_ip = $ip_extra . '/128';
                            push (@extra_ipaddr_out, $ipv6_ip);
                        }
                        # Print if there is anything to print:
                        if (scalar(@extra_ipaddr_out) gt "0") {
                            &debug_msg('IPV6ADDR_SECONDARIES="' . join(" ", @extra_ipaddr_out) . '"' . "\n"); 
                            print $fout 'IPV6ADDR_SECONDARIES="' . join(" ", @extra_ipaddr_out) . '"' . "\n";
                        }
                    }
                }
            }
        }
        return 1;
    }
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
# Copyright (c) 2017-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017-2018 Team BlueOnyx, BLUEONYX.IT
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