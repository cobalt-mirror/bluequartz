#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: tomcat_toggle.pl
# Tomcat Virtual Site Monitor
#
# Test whether any virtual sites are using Java.
# en/disable asp-apache-3000 if necessary
#
# we determine whether Java is "enabled" if any virtual site is subscribed.

my $DEBUG = 0;
$DEBUG && warn `date`."$0\n";

use CCE;
my $cce = new CCE;
$cce->connectfd();

my $object = $cce->event_object();

# Test proposed Vsite.Java state

my $oid;
my $enabled = 0;
my ($ok, $siteJsp) = $cce->get($cce->event_oid(), 'Java');

$enabled = 1 if ($siteJsp->{enabled});

# search namespace, so there is no need to get all Java namespaces
unless($enabled)
{
	my @voids = $cce->find('Vsite', { 'Java.enabled' => 1 });
	$DEBUG && warn "vsites with java: ".join(':',@voids)."\n";
	if (scalar(@voids))
	{
		$enabled = 1;
	}
}

my ($sysoid) = $cce->find("System");
$DEBUG && warn "Enable JSP? $enabled; system OID: $sysoid\n";

my ($status, $obj) = $cce->get($sysoid, "Java");

# fix chkconfig information:
if ($obj->{enabled} && !$enabled) 
{
	$DEBUG && warn "Calling CCE to disable System.Java enabled...\n";
	$ok = $cce->set($sysoid, 'Java', {'enabled' => 0});
} 
elsif (!$obj->{enabled} && $enabled) 
{
	$DEBUG && warn "Calling CCE to enable System.Java enabled...\n";
	$ok = $cce->set($sysoid, 'Java', {'enabled' => 1});
}

# do not block w/failures
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