#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: 50_initialize_ruleset.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2002, Sun Microsystems, Inc., All rights reserved.
#
# Builds a set of good-practice firewall rules.
# Opens listed ports to restricted_nics.  Denies all connections to
# closed_nics, and allows all access to nics.
#
# Ports are hash integer ranges; base => upper
#
# This is run once, on first activation of CCE.  Error detection for
# failed cce transactions are ignored--They have no context in a 
# factory constructor.  Oh well.

my $DEBUG = 0;
my $chain = 'input';

# default description, not used in UI as of 8/02
my $description = 'Default Ruleset'; 	

# The owner is essential to identifying this default firewall config
my $owner = 'default'; 	

# wide-open, restricted, and closed network interfaces
my @nics = ('lo', 'eth0', 'ipsec0', 'ipsec1', 'ipsec2'); 
my @restricted_nics = ('eth1', 'ppp0');	# WAN, selective
my @closed_nics = (); 			# Denies all

# each protocol must have a hash port map of the same name
my @protocols = ('tcp', 'udp', 'icmp', 'all');

my %pr;					# port range
$pr{'udp'} = {
	7 => 7,		# echo
	20 => 23,	# ftp, telnet, ssh
	25 => 25,	# smtp
	42 => 42,	# nameserver
	53 => 53,	# dns
	110 => 110,	# pop
	123 => 123,	# ntp
	143 => 143,	# imap
	220 => 220,	# imap3
	389 => 389,	# ldap
	161 => 162,	# snmp and snmp-trap
	520 => 520,	# RIP; not used ???
	1024 => 65535,	# active mode ftp
	};
$pr{'tcp'} = {
	7 => 7,		# echo
	20 => 23,	# ftp, telnet, ssh
	25 => 25,	# smtp
	42 => 42,	# nameserver
	53 => 53,	# dns
	110 => 110,	# pop
	123 => 123,	# ntp
	143 => 143,	# imap
	220 => 220,	# imap3
	389 => 389,	# ldap
	443 => 444,	# Cobalt admserv
	1024 => 65535,	# user apps
	};
$pr{'icmp'} = {
	};
$pr{'all'} = {
	};

use CCE;
my $cce = new CCE;
$cce->connectuds();

my $sysoid = ($cce->find('System'))[0];
my ($ok, $fw) = $cce->get($sysoid, 'Firewall');

if(!$ok || $fw->{initialized}) {
	$cce->bye('SUCCESS');
	exit 0;
}

# delete an existing default ruleset (Will's paranoia)
foreach my $rip ($cce->find('FirewallRule', {'owner' => $owner})) {
	$cce->destroy($rip);
}

# cycle through each nic, adding rules as necessary
foreach my $nic (@nics) {
	$DEBUG && warn "opening access to $nic";
	# add all-accept rule
	$cce->create('FirewallRule', {
		'policy' => 'ACCEPT',
		'interface' => $nic,
		'protocol' => 'all',
		'owner' => $owner,
		'description' => $description,
		}, undef);
}
foreach my $nic (@restricted_nics) {
	$DEBUG && warn "selectively access to $nic";
	foreach my $proto (@protocols) {
		while (my($port,$up) = each %{$pr{$proto}}) {
			$DEBUG && warn "protocol: $proto, base port: $port, upper: $up\n";
			$cce->create('FirewallRule', {
				'policy' => 'ACCEPT',
				'interface' => $nic,
				'dest_ports' => $port.':'.$up,
				'protocol' => $proto,
				'owner' => $owner, 
				'description' => $description,
				}, undef);
		}
	}
}


# Create/edit the $chain & set default policy and associate the default ruleset
my @chains = $cce->find('FirewallChain', {'name' => $chain});
my @new = $cce->find('FirewallRule', {'owner' => $owner});
if ($chains[0]) {
	$DEBUG && warn "Found chain policy $oid, setting DENY";

	my ($ok, $chain) = $cce->get($oid);
	my @old = $cce->scalar_to_array( $chain->{rules} );
	push (@old, @new);
	my $rules = $cce->array_to_scalar( @old );

	$cce->set($chains[0], undef, {
		'default' => 'DENY',
		'rules' => $rules,
		});
} else {
	$DEBUG && warn "Creating $chain chain policy";

	my $rules = $cce->array_to_scalar( @new );
	$cce->create('FirewallChain', {
		'name' => $chain,
		'default' => 'DENY',
		'rules' => $rules,
		}, undef);
}

# Mark initialized, enable, commit
my $time = time();
$DEBUG && warn "commit changes at $time";
$cce->set($sysoid, 'Firewall', {
	'initialized' => 1,
	'enabled' => 1,
	});
$cce->set($sysoid, 'Firewall', {
	'commit' => $time,
	});

$cce->bye('SUCCESS');
exit 0;


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
