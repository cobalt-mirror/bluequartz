#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/network
# $Id: range_ok.pl
# Given an IPPoolingRange object, checks that both min and max are
# present, and that min <= max,

use CCE;
use IpPooling;
use Net::IP qw(:PROC);

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

if (!$object->{min} || !$object->{max}) {
    $cce->warn('missing_part_of_range');
    $cce->bye('FAIL');
    exit 1;
}

$ip_one = Net::IP->new($object->{min});
$ip_two = Net::IP->new($object->{max});

# Check if IP's are either IPv4 or IPv6:
$Proto_IP_one = $ip_one->version();
$Proto_IP_two = $ip_two->version();

&debug_msg("ip_one IT: " . $Proto_IP_one . "\n");
&debug_msg("ip_two IT: " . $Proto_IP_two . "\n");

# Check of both specified IP's are of the same protocol:
if ($Proto_IP_one ne $Proto_IP_two) {
    $cce->warn('both_ips_must_be_ipv4_or_ipv6', { 'range_min' => $object->{min}, 'range_max' => $object->{max}});
    $cce->bye('FAIL');
}

# Check if min is smaller than max IP:
if ($ip_one->bincomp('gt',$ip_two)) {
    $cce->warn('minGreaterThanMax', { 'range_min' => $object->{min}, 'range_max' => $object->{max}});
    $cce->bye('FAIL');
    exit 1;
}

if ($cce->event_is_create()) {
    $cce->set($cce->event_oid(), '', { 'creation_time' => time });
}

$cce->bye('SUCCESS');
exit 0;

#
### Subs
#

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
# Copyright (c) 2014-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2018 Team BlueOnyx, BLUEONYX.IT
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