#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: validate_slavezones.pl

use CCE;
use Base::Nettest qw(test_network_in_network);

my $cce = new CCE;
$cce->connectfd();

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/validate");
$DEBUG && warn `date`."$0\n";

my $obj = $cce->event_object();
my $new = $cce->event_new();
my $oid = $cce->event_oid();

# global error msgid set
my $error;

if(!$cce->event_is_destroy())
{
	### Duplicate test
	my $criteria = undef;

	foreach my $element ('ipaddr', 'domain', 'netmask')
	{
		$criteria->{$element} = $obj->{$element} if
			($obj->{$element});
		$criteria->{$element} = $new->{$element} if
			($new->{$element});
		$DEBUG && warn "$element: $new->{$element}, $obj->{element}\n";
	}
	my (@oids) = $cce->find('DnsSlaveZone', $criteria);
	$DEBUG && warn join(', ', @oids)."\n";
	foreach my $oidstance (@oids)
	{
		next if ($oidstance == $oid);
		$error .= '[[base-dns.invalidDuplicate]]';
	}

	### Zone boundary tests

	# Check for existing DnsSOA zones or zone overlap
	if($obj->{domain}) 
	{
		my (@soa) = $cce->find('DnsSOA', {'domainname'=>$obj->{domain}});
		$error .= '[[base-dns.primaryZoneDuplicate]]' if
			($#soa >=0);
	}
	else
	{
		# Fetch all primary network zones, we'll match test each one
		my (@soa) = $cce->find('DnsSOA', {'domainname'=>''});
		foreach my $oidinstance (@soa) 
		{
			my($ok, $soa) = $cce->get($oidinstance);
			$error .= '[[base-dns.primaryZoneOverlap]]'
				if( Base::Nettest::test_network_in_network(
					$obj->{ipaddr},
					$obj->{netmask},
					$soa->{ipaddr},
					$soa->{netmask},
					));
		}

		# Fetch all slave network zones
		my (@slaves) = $cce->find('DnsSlaveZone', {'domain'=>''});
		foreach my $oidinstance (@slaves) 
		{
			next if ($oidinstance == $oid);

			my($ok, $slave) = $cce->get($oidinstance);
			$error .= '[[base-dns.slaveZoneOverlap]]'
				if( Base::Nettest::test_network_in_network(
					$obj->{ipaddr},
					$obj->{netmask},
					$slave->{ipaddr},
					$slave->{netmask},
					));
		}
	}
}

if($error)
{
	$cce->bye('FAIL', $error);
	exit 1;
} 
else
{
	$cce->bye('SUCCESS');
	exit 0;
}

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