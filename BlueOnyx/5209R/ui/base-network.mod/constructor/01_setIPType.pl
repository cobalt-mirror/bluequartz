#!/usr/bin/perl -I/usr/sausalito/perl -I.

# Set IPType in CODB

# this needs use lib because it isn't a handler
use lib qw(/usr/sausalito/handlers/base/network);
use CCE;

$DEBUG = "0";
if ($DEBUG) {
        use Data::Dumper;
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $errors = 0;

my $cce = new CCE;
$cce->connectuds();

my ($sysoid) = $cce->find('System');
my ($ok, $System) = $cce->get($sysoid);
if (!$ok) {
    &debug_msg("Running: No 'System' Object found, bailing.\n");
    $cce->bye('FAIL');
    exit 1;
}

# Are we an OpenVZ master-node?
if ((-e "/proc/user_beancounters") && (-f "/etc/vz/conf/0.conf")) {
    # Yes, we are.
    $device = 'venet0:0';
}
elsif ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
    # No, we're in an OpenVZ VPS:
    $device = 'venet0';
}
else {
    # No, we are not.
    $device = 'eth0';
}

@arr_assigned_ipv4 = split (/\n/, `LC_ALL=C /sbin/ip address show dev $device |grep inet|grep global|awk -F "inet " '{print \$2}'|awk -F " brd " '{print \$1}'|cut -d / -f1|sed '/^\$/d'`);
@arr_assigned_ipv6 = split (/\n/, `LC_ALL=C /sbin/ip address show dev $device |grep inet|grep global|awk -F "inet6 " '{print \$2}'|awk -F " brd " '{print \$1}'|cut -d / -f1|sed '/^\$/d'`);

$numIP4 = scalar(@arr_assigned_ipv4);
$numIP6 = scalar(@arr_assigned_ipv6);

# Got only IPv4:
if (($numIP4 gt '0') && ($numIP6 eq '0')) {
    # Under OpenVZ:
    if ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
        $IPType = 'VZv4';
    }
    else {
        $IPType = 'IPv4';
    }
}
# Only IPv6
elsif (($numIP4 eq '0') && ($numIP6 gt '0')) {
    # Under OpenVZ:
    if ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
        $IPType = 'VZv6';
    }
    else {
        $IPType = 'IPv6';
    }
}
# Got both IPv4 and IPv6:
elsif (($numIP4 gt '0') && ($numIP6 gt '0')) {
    # Under OpenVZ:
    if ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
        $IPType = 'VZBOTH';
    }
    else {
        $IPType = 'BOTH';
    }
}
else {
    # Under OpenVZ:
    if ((-e "/proc/user_beancounters") && (!-f "/etc/vz/conf/0.conf")) {
        $IPType = 'VZBOTH';
    }
    else {
        $IPType = 'BOTH';
    }
}

# Set IPType:
my ($ok) = $cce->set($sysoid, '', {'IPType' => $IPType});

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2017-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017-2018 Team BlueOnyx, BLUEONYX.IT
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

