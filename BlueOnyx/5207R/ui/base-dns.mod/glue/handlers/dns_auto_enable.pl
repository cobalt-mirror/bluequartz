#!/usr/bin/perl -w
# $Id: dns_auto_enable.pl
#
# Detects self-reference in DNS servers, enables named accordingly

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/dns_auto_enable");
$DEBUG && warn `date` .' '. $0;

use lib qw( /usr/sausalito/perl );
use CCE;
$cce = new CCE;
$cce->connectfd();

my ($sysoid) = $cce->find('System');
my ($ok, $obj) = $cce->get($sysoid, 'DNS');

# Do nothing if bind is already enabled
if($obj->{enabled})
{
	$DEBUG && warn "named already enabled, exiting\n";
	$cce->bye('SUCCESS');
	exit 0;
}

my ($sok, $system) = $cce->get($sysoid, "");
$DEBUG && warn 'system DNS servers: '.$system->{dns}."\n";
my $enable_named = 0;

# This special case is caught by the setup wizard
$enable_named = 1 if ($system->{dns} =~ /&127.0.0.1&/);

my (@netoids) = $cce->find('Network');
foreach my $oid (@netoids)
{
	last if ($enable_named);

	my ($ok, $net) = $cce->get($oid);
	my $search = '&'.$net->{ipaddr}.'&';
	$DEBUG && warn "Per-network match: $search\n";
	if($system->{dns} =~ /$search/)
	{
		$enable_named = 1;
	}
}

if($enable_named)
{
	$DEBUG && warn "Enabling bind...\n";
	$cce->set($sysoid, 'DNS', {'enabled' => 1});
}

$cce->bye('SUCCESS');
exit 0;

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