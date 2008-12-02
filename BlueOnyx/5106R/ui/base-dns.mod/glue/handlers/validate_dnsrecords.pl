#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: validate_dnsrecords.pl 850 2006-08-13 03:47:25Z bsmith $
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
	# Duplicate test
	my $criteria = undef;

	foreach my $element ('type', 'ipaddr', 'hostname',
				'domainname', 'mail_server_name',
				'alias_domainname', 'alias_hostname')
	{
		$criteria->{$element} = $obj->{$element} if
			($obj->{$element});
		$criteria->{$element} = $new->{$element} if
			($new->{$element} || $element eq 'hostname');
		$DEBUG && warn "$element: $new->{$element}, $obj->{$element}\n";
	}
	my (@oids) = $cce->find('DnsRecord', $criteria);
	$DEBUG && warn join(', ', @oids)."\n";
	foreach my $oidstance (@oids)
	{
		next if ($oidstance == $oid);
		# Temp removed.  Need to have better check method.  This
		# fails.  You are allowed dups in DNS.  Happens all of the
		# time.
		# $error .= '[[base-dns.invalidDuplicate]]';
	}

	### Zone boundary tests

	# Check for existing DnsSlaveZone zones or zone overlap
	if($obj->{type} eq 'PTR') 
	{
		# Fetch all slave network zones, we'll match test each one
		$DEBUG && warn "Comparing slave networks...\n";
		my (@slaves) = $cce->find('DnsSlaveZone', {'domain'=>''});
		foreach my $oidinstance (@slaves) 
		{
			my($ok, $slave) = $cce->get($oidinstance);
			$error .= '[[base-dns.secondaryZoneOverlap]]'
				if( Base::Nettest::test_network_in_network(
					$obj->{ipaddr},
					$obj->{netmask},
					$slave->{ipaddr},
					$slave->{netmask},
				));
			$DEBUG && warn "$obj->{ipaddr}, $obj->{netmask}, $slave->{ipaddr}, $slave->{netmask}\n";
		}
	}
	else
	{
		my (@soa) = $cce->find('DnsSlaveZone', 
			{'domain'=>$obj->{domainname}});
		$error .= '[[base-dns.secondaryZoneDuplicate]]' if
			($#soa >=0);
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
