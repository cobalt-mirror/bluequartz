#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: 40_addGateway.pl

# this needs use lib because it isn't a handler
use lib qw(/usr/sausalito/handlers/base/network);
use CCE;
use Sauce::Config;
use Sauce::Util;
use Sauce::Service;
use Network;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
        use Data::Dumper;
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $errors = 0;

my $conf = '/etc/sysconfig/network';
my $gateway = '';
my $gateway_IPv6 = '';

my $cce = new CCE;
$cce->connectuds();

&debug_msg("Running: starting\n");

my @oids = $cce->find('System');
if (not @oids) {
    $cce->bye('FAIL');
    exit 1;
}

my ($ok, $obj) = $cce->get($oids[0]);
unless ($ok and $obj) {
    $cce->bye('FAIL');
    exit 1;
}

# Start sane:
$IPType = "BOTH";
$gateway = '';
$gateway_IPv6 = '';
$gatewaydev_IPv6 = '';
$NETWORKING_IPV6 = '';

# Parse /etc/sysconfig/network:
$sys_network = "/etc/sysconfig/network";
if (-f $sys_network) {
    open (F, "/etc/sysconfig/network") || die "Could not open $sys_network $!";
    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;               # skip blank lines
        next if $line =~ /^#$/;                 # skip comments  
        if ($line =~ /^GATEWAY=(.*)$/) {
            $gateway = $1;
            if ($gateway =~ /^\"(.*)\"/g) {
                $gateway = $1;
            }
        }
        if ($line =~ /^IPV6_DEFAULTGW=(.*)$/) {
            $gateway_IPv6 = $1;
            if ($gateway_IPv6 =~ /^\"(.*)\"/g) {
                $gateway_IPv6 = $1;
            }
        }
        if ($line =~ /^IPV6_DEFAULTDEV=(.*)$/) {
            $gatewaydev_IPv6 = $1;
            if ($gatewaydev_IPv6 =~ /^\"(.*)\"/g) {
                $gatewaydev_IPv6 = $1;
            }
        }
        if ($line =~ /^GATEWAYDEV=(.*)$/) {
            $gatewaydev = $1;
            if ($gatewaydev =~ /^\"(.*)\"/g) {
                $gatewaydev = $1;
            }
        }
        if ($line =~ /^NETWORKING_IPV6=(.*)$/) {
            $NETWORKING_IPV6 = $1;
            if ($NETWORKING_IPV6 =~ /^\"(.*)\"/g) {
                $NETWORKING_IPV6 = $1;
            }
        }
    }
    close(F);
}

#
### Determine which IP protocol is available: IPv4, IPv6 or BOTH:
#

# Are we an OpenVZ master-node?
if ((-e "/proc/user_beancounters") && (-f "/etc/vz/conf/0.conf")) {
    # Yes, we are.
    if (($gateway ne '') && ($gateway_IPv6 ne '')) {
        $IPType = 'BOTH';
    }
    elsif (($gateway eq '') && ($gateway_IPv6 ne '')) {
        $IPType = 'IPv6';
    }
    elsif (($gateway ne '') && ($gateway_IPv6 eq '')) {
        $IPType = 'IPv4';
    }
}
elsif ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
    # No, we're in an OpenVZ VPS. Here we have the problem that $gateway_IPv6
    # is not set. But we do have $gatewaydev_IPv6 set to 'venet0'.
    if (($gateway eq '192.0.2.1') && ($gatewaydev_IPv6 eq 'venet0') && (-f '/etc/sysconfig/network-scripts/ifcfg-venet0:0')) {
        $IPType = 'VZBOTH';
    }
    elsif (($gateway eq '192.0.2.1') && ($gatewaydev_IPv6 eq 'venet0') && (!-f '/etc/sysconfig/network-scripts/ifcfg-venet0:0')) {
        $IPType = 'VZv6';
    }
    elsif (($gateway eq '192.0.2.1') && ($gatewaydev_IPv6 ne 'venet0') && (-f '/etc/sysconfig/network-scripts/ifcfg-venet0:0')) {
        $IPType = 'VZv4';
    }    
}
else {
    # No, we are neither an OpenVZ node nor OpenVZ Container:
    if (($gateway ne '') && ($gateway_IPv6 ne '')) {
        $IPType = 'BOTH';
    }
    elsif (($gateway eq '') && ($gateway_IPv6 ne '')) {
        $IPType = 'IPv6';
    }
    elsif (($gateway ne '') && ($gateway_IPv6 eq '')) {
        $IPType = 'IPv4';
    }
}

&debug_msg("IPType: $IPType\n");

if ((!$gateway) || ((-f "/proc/user_beancounters") && (!$gatewaydev))) {

    # Attempt to determine Gateway through other means:
    if ( ! -f "/proc/user_beancounters" ) {

        if (($IPType eq 'IPv6') || ($IPType eq 'VZv6')) {
            $pingTarget = "2001:4860:4860::8888";
        }
        else {
            $pingTarget = "8.8.8.8";
        }

        # Test network connection:
        $test1 = &pingtest($pingTarget);
        &debug_msg("Ping-Test1: $test1\n");
        if ($test eq "1") {
            # Not running on OpenVZ, so check the route to find the Gateway:
            my $data = `$Network::ROUTE -n|grep '^0\.0\.0\.0'`;
            if ($data =~ /0\.0\.0\.0\s+((\d+).(\d+).(\d+).(\d+))/) {
                $gateway = $1;
            }
        }
    }
    else {

        # We're on OpenVZ. See if we have either GATEWAY or GATEWAYDEV defined:
        if ((!$gateway) || (!$gatewaydev)) {
            # At least either GATEWAY or GATEWAYDEV are undefined. Test network connectivity 
            # to see if we can establish a network connection to the outside:

            $test2 = &pingtest($pingTarget);
            &debug_msg("Ping-Test2: $test2\n");
            if (test2 eq "1") {
                # Network is dead. We need to fix it.
                
                # Build output hash:   
                if ((!$gateway) && (!$gatewaydev)) {
                        $server_sys_network_writeoff = {
                        'GATEWAY' => '"192.0.2.1"',
                        'GATEWAYDEV' => '"venet0"'
                        };
                }
                elsif ((!$gateway) && ($gatewaydev)) {
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
        }
    }
}

&debug_msg("Gateway is $gateway - \$gateway_IPv6: $gateway_IPv6 - \$gatewaydev: $gatewaydev - \$IPType: $IPType\n");
if (($gateway) || ($gateway_IPv6) || ($IPType)) {
    # Only do a SET transaction if there are changes that need to be applied:
    if (($gateway ne $obj->{gateway}) || ($gateway_IPv6 ne $obj->{gateway_IPv6}) || ($IPType ne $obj->{IPType})) {
        &debug_msg("Updating CODB Obj.: $oids[0]\n");
        $cce->set($oids[0], '', {'gateway' => $gateway, 'gateway_IPv6' => $gateway_IPv6, 'IPType' => $IPType });
    }
}

# Done:
$cce->bye('SUCCESS');
exit(0);

sub pingtest($$) {
    my ($ping) = @_;
    if (($IPType eq 'IPv6') || ($IPType eq 'VZv6')) {
        system(sprintf("ping6 -q -w 3 -c 1 %s >/dev/null", $ping));
    }
    else {
        system(sprintf("ping -q -w 3-c 1 %s >/dev/null", $ping));
    }
    $retcode = $? >> 8;
    # ping returns 1 if unable to connect
    return $retcode;
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
# Copyright (c) 2015-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2018 Team BlueOnyx, BLUEONYX.IT
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