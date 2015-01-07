#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: 40_addGateway.pl

# this needs use lib because it isn't a handler
use lib qw(/usr/sausalito/handlers/base/network);
use CCE;
use Sauce::Config;
use Sauce::Util;
use Network;

my $DEBUG = 0;

my $errors = 0;

my $conf = '/etc/sysconfig/network';
my $gateway = '';

my $cce = new CCE;
$cce->connectuds();

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

# Modify /etc/sysconfig/network-scripts/ifup-routes if we're on OpenVZ:
if ( -f "/proc/user_beancounters" ) {
    &fix_if_up;
}

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
        if ($line =~ /^GATEWAYDEV=(.*)$/) {
            $gatewaydev = $1;
            if ($gatewaydev =~ /^\"(.*)\"/g) {
                $gatewaydev = $1;
            }
        }
    }
    close(F);
}

if ((!$gateway) || ((-f "/proc/user_beancounters") && (!$gatewaydev))) {

    # Attempt to determine Gateway through other means:
    if ( ! -f "/proc/user_beancounters" ) {

        # Test network connection:
        $test1 = &pingtest("8.8.8.8");

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
    	    use Net::Ping;
    	    $p = Net::Ping->new();
    	    $host = "8.8.8.8";
    	    if (!$p->ping($host)) {
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
        	system("/sbin/service network restart > /dev/null 2>&1");
    	    }
    	    $p->close();
	}
    }
}

($DEBUG && $gateway) && print STDERR "Gateway is $gateway\n";

if ($gateway) {
    $cce->set($oids[0], '', {'gateway' => $gateway});
    $cce->bye();
}
exit 0;

sub pingtest($$) {
  my ($ping) = @_;
  system(sprintf("ping -q -c 1 %s>/dev/null", $ping));
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

sub fix_if_up {

    # Now this is really dirty. If we're on OpenVZ, then a network restart will drop our custom routes. Sure, if we
    # have the proper fake GATEWAY and GATEWAYDEV set, then we retain our network connectivity. Still, the routing 
    # table will have only a single route left. Which is a bit misleading and may throw off other applications.
    # To compensate for this, we add a single line to the very bottom of /etc/sysconfig/network-scripts/ifup-routes 
    # to execute  '/usr/sausalito/handlers/base/network/change_route.pl -c 2' whenever the OS tries to set up routes
    # for the network device 'venet:0:0'. This recreates the full routing table that we expect to see.

    # Check if ifup-routes exists:
    if (-f "/etc/sysconfig/network-scripts/ifup-routes") {
        # If it exists, check if it already has our 'venet0:0' provisions in it:
        open (F, "/etc/sysconfig/network-scripts/ifup-routes") || die "Could not open /etc/sysconfig/network-scripts/ifup-routes $!";
        while ($line = <F>) {
            chomp($line);
            next if $line =~ /^\s*$/;               # skip blank lines
            next if $line =~ /^#$/;                 # skip comments
            if ($line =~ /\"venet0:0\"/g) {
                # Provisions found:
                $result = "found";
            }
        }
        close(F);

        # OpenVZ 'venet0:0' provisions not found. Adding them:
        if (!$result) {
            system('echo \'if [[ "$1" =~ "venet0:0" ]];then /usr/sausalito/handlers/base/network/change_route.pl -c 2; fi\' >> /etc/sysconfig/network-scripts/ifup-routes');
        }
    }
}

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