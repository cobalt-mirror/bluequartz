#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: network_destroy.pl
#
# ensure that there is always an interface or alias with the IP address of
# each vsite

use CCE;
use Vsite;

my $cce = new CCE;
$cce->connectfd();

# get network info
my $network = $cce->event_object();
my $old_network = $cce->event_old();

# make sure there are no vsites using the ip address that is going away
# use the old ip address value, because that is always available
# and will be correct in case enabled and ipaddr are changing simultaneously
my @vsites = $cce->find('Vsite', { 'ipaddr' => $old_network->{ipaddr} });
my @networks = $cce->find('Network', { 
                                        'ipaddr' => $old_network->{ipaddr},
                                        'enabled' => 1
                                      });

if (!$network->{enabled} && scalar(@vsites) && !scalar(@networks)) {
    # just fail if it is an alias
    if (!$network->{real}) {
        &fail($cce);
    }
    else {
        # interface is going down, try to migrate to alias on another real
        # interface
        my $candidate = &get_candidate_iface($cce);
    
        if (!$candidate) { &fail($cce); }
        vsite_add_network_interface($cce, $old_network->{ipaddr}, $candidate);
    }

}
elsif ($old_network->{ipaddr} && scalar(@vsites) && $network->{ipaddr} ne $old_network->{ipaddr}) {
    # make sure there is an interface for this ip after the change
    $network->{device} =~ /^([^:]+)/;
    vsite_add_network_interface($cce, $old_network->{ipaddr}, $1);
}

$cce->bye('SUCCESS');
exit(0);

sub get_candidate_iface {
    if (! -f "/proc/user_beancounters") { 
            $DEFAULT_INTERFACE = 'eth0'; 
    } 
    else { 
            $DEFAULT_INTERFACE = 'venet0'; 
    } 
}

sub fail {
    my $cce = shift;

    $cce->bye('FAIL', '[[base-vsite.vsiteUsingIpAddress]]');
    exit(1);
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
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