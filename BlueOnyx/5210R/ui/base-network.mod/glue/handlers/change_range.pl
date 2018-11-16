#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/network
# $Id: change_range.pl
# If pooling is enabled, see if we can change this range.
# If after this change, all the ranges still encompass all the ips, then
# we can change this. Otherwise, we're not allowed to change this.

use CCE;
use IpPooling;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE('Domain' => 'base-network');
$cce->connectfd();

my $old = $cce->event_old();
my $object = $cce->event_object();
my $new = $cce->event_new();

my ($sysoid) = $cce->findx('System');
my ($ok, $network) = $cce->get($sysoid, 'Network');

# check against ips if pooling is enableda
if ($network->{pooling}) {
    my (@oids) = $cce->find('IPPoolingRange');
    my @range_oids = ();
    
    # we know we're being changed
    # so get all the OTHER ranges
    foreach $a_oid (@oids) {
        if ($a_oid != $object->{event_oid}) {
            my ($ok, $range) = $cce->get($a_oid);
            push @ranges, $range;
        }
    }

    &debug_msg("Ranges: " . Dumper (\@ranges) . "\n");

    # Get networks
    my (@network_oids) = $cce->findx('Network', { 'enabled' => 1 });
    foreach my $oid (@network_oids) {
        my ($ok, $net) = $cce->get($oid);
        if (!$ok) {
            $cce->bye('FAIL');
            exit 1;
        }
        if ((in_array(\@IPs, $net->{ipaddr})) || ($net->{ipaddr} eq "")) {
            # Nada
        }
        else {
            push @IPs, $net->{ipaddr};
        }
        if ((in_array(\@IPs, $net->{ipaddr_IPv6})) || ($net->{ipaddr_IPv6} eq "")) {
            # Nada
        }
        else {
            push @IPs, $net->{ipaddr_IPv6};
        }
    }

    # Get Vsites
    my (@vsite_oids) = $cce->findx('Vsite');
    foreach my $void (@vsite_oids) {
        my ($ok, $vsite) = $cce->get($void);
        if (!$ok) {
            $cce->bye('FAIL');
            exit 1;
        }
        if ((in_array(\@IPs, $vsite->{ipaddr})) || ($vsite->{ipaddr} eq "")) {
            # Nada
        }
        else {
            push @IPs, $vsite->{ipaddr};
        }
        if ((in_array(\@IPs, $vsite->{ipaddrIPv6})) || ($vsite->{ipaddrIPv6} eq "")) {
            # Nada
        }
        else {
            push @IPs, $vsite->{ipaddrIPv6};
        }        
    }

    # Remove duplicates:
    my @filtered_IPs = uniq(@IPs);

    &debug_msg("IPs: " . Dumper (\@filtered_IPs) . "\n");

    # Validate IPs:
    my (@error_ips) = IpPooling::validate_pooling_state(\@ranges, \@filtered_IPs);
    if (@error_ips) {
        &debug_msg("error_ips: " . Dumper (\@error_ips) . "\n");
        $cce->warn('cant_change_range', { 'affected_ips' => join(',', @error_ips)});
        $cce->bye('FAIL');
        exit 1;
    }
}

$cce->bye('SUCCESS');
exit 0;

#
### Subs:
#

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
# Copyright (c) 2015-2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2017 Team BlueOnyx, BLUEONYX.IT
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