#!/usr/bin/perl
# $Id: check_pool.pl
#
# Description:
#	Runs on Network creation, or change of Network.ipaddr property.
#       If IP pooling enabled, checks that the given IP address is
#       within the pool of acceptable IPs.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Data::Dumper;
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

use lib qw(/usr/sausalito/perl);
use lib qw(/usr/sausalito/handlers/base/network);
use CCE;
use IpPooling;

# FIXME. Passing in i18n domain, needed to proper I18n msgs
# and variable interpolation by cce client libs.
my $cce = new CCE('Domain' => 'base-network');

$cce->connectfd();

&debug_msg("Starting check_pool.pl.\n");

my $network_new = $cce->event_new();

my ($sysoid) = $cce->find('System');
my ($ok, $network) = $cce->get($sysoid, 'Network');
if (!$ok) {
    $cce->bye('FAIL');
    exit 1;
}

&debug_msg("Pooling enabled: " . $network->{pooling} . "\n");

if ($network->{pooling} && ($network_new->{ipaddr} || $network_new->{ipaddr_IPv6})) {
    my (@oids) = $cce->find('IPPoolingRange');
    if (!$oids) {
        # IP-Pooling is enabled. However, no IP-Pooling range has been set up YET.
        # If we don't exit here without failure, then this will prevent the creation
        # of the initial Network objects that we need for a functioning network.
        # So we exit here now and just be done with it until someone configures the
        # IP pooling ranges. Better safe than sorry.
        $cce->bye('SUCCESS');
        exit 0;        
    }
    my @ranges = ();
    my @IPs = ();

    if ($network_new->{ipaddr_IPv6}) {
        push @IPs, $network_new->{ipaddr_IPv6};
    }
    if ($network_new->{ipaddr}) {
        push @IPs, $network_new->{ipaddr};
    }

    # get ranges
    foreach $a_oid (@oids) {
    	my ($ok, $range) = $cce->get($a_oid);
    	if (!$ok) {
    	    $cce->bye('FAIL');
    	    exit 1;
    	}
    	push @ranges, $range;
    }

    # Remove duplicates:
    my @filtered_IPs = uniq(@IPs);
    my (@error_ips) = IpPooling::validate_pooling_state(\@ranges, \@filtered_IPs);
    if (@error_ips) {
        my $offenders = join(", ", @error_ips);
        &debug_msg("Warn: ip_restricted - " . $offenders . " \n");
        $cce->warn('ip_restricted', {'ipaddr' => $offenders});
        $cce->bye('FAIL');
        exit 1;
    }
}

$cce->bye('SUCCESS');
exit 0;

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
# Copyright (c) 2013-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013-2018 Team BlueOnyx, BLUEONYX.IT
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