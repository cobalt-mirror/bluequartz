#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: enableAM.pl
#
use strict;
use CCE;

my $cce = new CCE;
$cce->connectfd();

# retreive object data:
my $oid = $cce->event_oid();
my $ns = $cce->event_namespace();
my $prop = $cce->event_property();
my ($ok, $newobj, $oldobj) = $cce->get($oid, $ns);

if (!$oid || !$ns ||!$prop) {
  $cce->bye('FAIL', 'Bad oid or namespace');
  exit(1);
}

my ($oldval, $newval);
$oldval = $oldobj->{$prop} ? 1 : 0;
$newval = $newobj->{$prop} ? 1 : 0;

my %nsmap = (Java => "Java", enableHttp => "Java_http", enableAjp12 => "Java_ajp12");
my $nsprop;

# only set on a real change of boolean value, not just string value
if (!($oldval eq $newval)) {
	my @oids = $cce->find("ActiveMonitor");
	# enable or disable all parts together
	foreach $nsprop (("Java", "enableAjp12")) { # We don't use Tomcat's port 8080 "enableHttp"
	    $cce->set($oids[0], $nsmap{$nsprop}, {enabled => $newval});
	}
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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