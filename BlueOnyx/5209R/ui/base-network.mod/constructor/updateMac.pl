#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/network
# $Id$

use strict;
use CCE;
use Network;

my $DEBUG = 0;

my $cce = new CCE;
$cce->connectuds();

# Are we on OpenVZ?
if (-f "/proc/user_beancounters") { 
    # Apparently yes. Stopping right here, as we have no MACs.
    exit(0);
}

my @oids = $cce->find('Network');
foreach my $oid (@oids) {
  my ($ok, $obj) = $cce->get($oid);
  my $device = $obj->{'device'};

  my $data = `$Network::IFCONFIG $device`;

  my $hex = '[0-9a-fA-F]{2,}';
  if ($data =~ m/HWaddr ($hex:$hex:$hex:$hex:$hex:$hex)/) {
    my $mac = $1;
    $DEBUG && print STDERR "mac address for device $device: $mac\n";
    my ($ok) = $cce->set($oid, '', { 'mac' => $mac });
  }
 
  # For 5209R (as ifconfig has a new format):
  if ($data =~ m/ether ($hex:$hex:$hex:$hex:$hex:$hex)/) {
    my $mac = $1;
    $DEBUG && print STDERR "mac address for device $device: $mac\n";
    my ($ok) = $cce->set($oid, '', { 'mac' => $mac });
  }
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#  notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#  notice, this list of conditions and the following disclaimer in 
#  the documentation and/or other materials provided with the 
#  distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#  contributors may be used to endorse or promote products derived 
#  from this software without specific prior written permission.
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