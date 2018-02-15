#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: no_duplicates.pl
#
# don't allow the creation of Network aliases with the same IP address
# as another alias or a real interface.  Also don't allow a real interface
# to have it's IP address changed to that of another real interface.

use CCE;

my $cce = new CCE('Domain' => 'base-network');
$cce->connectfd();

my $new_net = $cce->event_object();

# make sure the device is unique
my @devices = $cce->find('Network', { 'device' => $new_net->{device} });

# there should be only the current object found above
if (scalar(@devices) > 1)
{
    $cce->bye('FAIL', 'noDuplicateDevices', { 'device' => $new_net->{device} });
    exit(1);
}

if (! -f "/proc/user_beancounters") {
    # Normal network devices (eth):
    # do things differently if this is real or an alias
    my $real = 0;
    if ($new_net->{device} =~ /^eth\d$/) {
        $real = 1;
    }
}
else {
    # OpenVZ's venet network devices:
    # do things differently if this is real or an alias
    my $real = 0;
    if ($new_net->{device} =~ /^venet\d$/) {
        $real = 1;
    }
}

my @old_nets = $cce->find('Network', 
              { 
                  'ipaddr' => $new_net->{ipaddr},
                  'ipaddr_IPv6' => $new_net->{ipaddr_IPv6}, 
                  'enabled' => 1
                  });

# look for just real interfaces, in case the interface being modified is real
my @real_nets = $cce->find('Network',
                        {
                            'ipaddr' => $new_net->{ipaddr},
                            'ipaddr_IPv6' => $new_net->{ipaddr_IPv6}, 
                            'enabled' => 1,
                            'real' => 1
                        });

# only bother if it's enabled and it has a real ip address assigned
if ($new_net->{enabled} && (($new_net->{ipaddr} ne '') && ($new_net->{ipaddr} ne '0.0.0.0') || ($new_net->{ipaddr_IPv6} ne ''))) { 

    # fail if it's an alias and a Network object exists with the same IP
    # also fail if it's real and another real interface already has this IP
    if (((scalar(@old_nets) > 1) && !$real) || 
        ((scalar(@real_nets) > 1) && $real))

    {
        $cce->bye('FAIL', 'noDuplicateIPsAllowed', { 'ipaddr' => $new_net->{ipaddr} }); 
        exit(1);
    }
} # end if enabled with a valid ip address

# otherwise, it is okay
$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017 Team BlueOnyx, BLUEONYX.IT
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