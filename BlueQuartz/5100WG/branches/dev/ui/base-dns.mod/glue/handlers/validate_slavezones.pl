#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: validate_slavezones.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2001 Sun Microsystems, Inc., All rights reserved.

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


# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
