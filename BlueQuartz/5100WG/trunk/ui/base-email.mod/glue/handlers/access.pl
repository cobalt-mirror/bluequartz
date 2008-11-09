#!/usr/bin/perl -w -I/usr/sausalito/perl -I.

use strict;
use CCE;
use Email;
use Sauce::Util;

my $cce = new CCE( Domain => 'base-email' );

$cce->connectfd();

my $Sendmail_access = Email::AccessDB;
my $sys_obj;
my $sys_oid;

# could have been triggered by a change in Network so check
if ($cce->event_object()->{CLASS} ne 'System') {
	($sys_oid) = $cce->find("System");
	(my $ok, $sys_obj) = $cce->get($sys_oid);

	if (not $ok) {
		$cce->bye('FAIL');
		exit(1);
	}
} else {
	$sys_oid = $cce->event_oid();
	$sys_obj = $cce->event_object();
}

my ($ok, $obj) = $cce->get($sys_oid, "Email");

if (not $ok) {
	$cce->bye('FAIL');
	exit(1);
}

# get network objects so we can allow relaying for the nets our interfaces
# are on
my @net_oids = $cce->find("Network");

my @interfaces = ();

for my $oid (@net_oids) {
	my ($ok, $interface) = $cce->get($oid);

	if ($ok) {
		push @interfaces, $interface;
	}
}

# create the access file
my $access_list = &make_deny($obj, $sys_obj, \@interfaces);
if (not $access_list) { 
	$cce->warn('duplicateKeys');
	$cce->bye('FAIL');
	exit(1);
}

if (!Sauce::Util::replaceblock($Sendmail_access, 
	'# Cobalt Access Section Begin', $access_list, 
	'# Cobalt Access Section End')
   	) {
	$cce->warn('[[base-email.cantEditFile]]', { 'file' => Email::AccessDB });
	$cce->bye('FAIL');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);


sub make_deny
{
	my $obj = shift;
	my $sys = shift;
	my $interfaces = shift;
	my $out = "";	
	my %spammers;
	my %relayAllow;

	map { $spammers{$_} = 1; } 
		( $cce->scalar_to_array($obj->{deniedHosts}), 
		$cce->scalar_to_array($obj->{deniedUsers}) );

	map { $relayAllow{$_} = 1; } $cce->scalar_to_array($obj->{relayFor});

	# relay for our domain in case we are the smtp server
	# but not if our domain is explicitly excluded in deniedHosts
	if(not $spammers{$sys->{domainname}}) {
		$relayAllow{$sys->{domainname}} = 1;
	}
	
	# relay for any networks we are on
	for my $interface (@{ $interfaces }) {
		next unless( $interface->{enabled} ); 
		
		my $netmask = pack 'C4', (split /\./, $interface->{netmask});
		my $address = pack 'C4', (split /\./, $interface->{ipaddr});

		# now figure out our network
		my $network = join '.', unpack('C4', ($address & $netmask));

		# if network is all zeros, don't even bother to add it
		next if ($network =~ /^0\.0\.0\.0$/);

		# only add these if they aren't already specified in relayAllow
		# or spammers
		if(not ($spammers{$network} || $relayAllow{$network})) {
			$relayAllow{$network} = 1;
		}
	}

	foreach my $spammer ( keys %spammers ) {
		$out .= "$spammer\t550 Mail rejected due to possible SPAM\n";
	}

	foreach my $relay ( keys %relayAllow ) {
		# make sure someone isn't in both relay and spam list
		if ($spammers{$relay}) {
			return "";
		}

		$relay =~ s'(\.0)+$''g; # network support
		$out .= "$relay\tRELAY\n";
	}

	return $out;
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
