#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/network
# $Id$

use strict;
use CCE;
use Network;

my $DEBUG = 0;

# Connect to CCE
my $cce = new CCE;
$cce->connectuds();

# Get system object, and some current values from it
my @oids = $cce->find('System');
my $sys_oid = $oids[0];
my ($ok, $sys_obj) = $cce->get($sys_oid, 'Network');
my $configure = $sys_obj->{'interfaceConfigure'};
my $pooling = $sys_obj->{'pooling'};

# If we're using DHCP (like in AWS) or if we're under OpenVZ, then we cannot
# change the IP address ranges via the GUI:
if (  (-f "/etc/is_aws") || (-f "/proc/user_beancounters")) {
	if ($configure == 1 ) {
		$configure = 0;
		$cce->set($sys_oid, 'Network', { 'interfaceConfigure' => $configure });
	}
} else {
	if ($configure == 0 ) {
		$configure = 1;
		$cce->set($sys_oid, 'Network', { 'interfaceConfigure' => $configure });
	}
}

# Add entries into IP pooling for all interfaces if they dont already exist
# from currently configured interfaces
if ($configure == 0) {
  my @net_oids = $cce->find('Network');
  foreach my $oid (@net_oids) {
    my ($ok, $obj) = $cce->get($oid);
    my $ipaddr = $obj->{'ipaddr'};
    @oids = $cce->find('IPPoolingRange', {'min' => $ipaddr});
    if ($#oids < 0) {
      $ok = $cce->create('IPPoolingRange', {'min' => $ipaddr, 'max' => $ipaddr});
    }
  }

  # Delete any entries for interfaces that have been deleted
  # Note: It is possible to fail on deletion if a vsite still exists
  # and the ip has been removed from the virtualisation layer.

  @oids = $cce->find('IPPoolingRange');
  foreach my $oid (@oids) {
    my ($ok, $obj) = $cce->get($oid);
    my $ipaddr = $obj->{'min'};
    my @net_oids = $cce->find('Network', {'ipaddr' => $ipaddr});
    if ($#net_oids < 0 ) {
      my ($ok) = $cce->destroy($oid);
    }
  }
  if ($pooling == 0) {
    my ($ok) = $cce->set($sys_oid, 'Network', {'pooling' => 1});
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
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
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
