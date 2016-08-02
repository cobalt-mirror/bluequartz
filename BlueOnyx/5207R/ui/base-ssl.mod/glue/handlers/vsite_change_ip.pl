#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: vsite_change_ip.pl
#
# make sure if a vsite's ip address is changed that two sites with the
# same IP address don't end up having SSL enabled
#

use CCE;

my $cce = new CCE('Domain' => 'base-ssl', 'Namespace' => 'SSL');
$cce->connectfd();

my $ssl_info = $cce->event_object();

# If SSL is not enabled, just exit with success
# Actually: We exit anyway as this script is no longer needed.
# It has also been disabled in the config, so it will no longer
# be called anyway.
if ((!$ssl_info->{enabled}) || ($ssl_info->{enabled})) {
	$cce->bye('SUCCESS');
	exit(0);
}

# need to get the vsite for the IP address
my ($ok, $vsite) = $cce->get($cce->event_oid());
if (!$ok) {
	$cce->bye('FAIL', 'cantGetVsite');
	exit(1);
}

my @other_sites = $cce->find('Vsite',
			     {
				'ipaddr' => $vsite->{ipaddr},
				'SSL.enabled' => 1
			     });

if (scalar(@other_sites) > 1) {
	$cce->bye('FAIL', 'sslMigrationConflict',
		  { 'ipaddr' => $vsite->{ipaddr} });
	exit(1);
}

# otherwise, everything is okay
$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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