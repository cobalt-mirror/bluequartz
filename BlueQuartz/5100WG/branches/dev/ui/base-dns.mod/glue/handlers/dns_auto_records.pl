#!/usr/bin/perl -w
# $Id: dns_auto_records.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2002 Sun Microsystems, Inc., All rights reserved.
#
# Detects system fqdn, network and dhcpd changes and manages DNS records
# appropriately.
#
# How this works:
# Class detection is made of the invoker, very simple differentiation except
# System, which can be invoked by System.DNS.auto_config or System.hostname, 
# domainname.  We use a global $enable flag to mark the former.  On $enable, 
# we walk through all the config sections, Network, fqdn, dhcp.  Otherwise,
# we only execute the class-specific config.
# If old settings are available "old" dns records are deleted on a best-effort
# basis prior to creating new records.   

my $DEBUG = 0;
if($DEBUG) { use strict; }
if($DEBUG) { use Data::Dumper; }
$DEBUG && open(STDERR, ">>/tmp/dns");
$DEBUG && warn "\n".`date`.' '.$0;

use lib qw( /usr/sausalito/perl );
use CCE;
my $cce = new CCE;
$cce->connectfd();

my ($sysoid) = $cce->find('System');
my ($ok, $system) = $cce->get($sysoid);
my ($aok, $dnsobj) = $cce->get($sysoid, 'DNS');

# Do nothing if auto-dns is not enabled
unless($dnsobj->{auto_config})
{
	$DEBUG && warn "Auto-configuration is disabled.  Doing nothing.\n";
	$cce->bye('SUCCESS');
	exit 0;
}

my $enable_named = 0;
my $dirty = 0; # flag to regen named db files

# Identify our dispatched event
my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();

$DEBUG && warn "    oid: $oid\n";
$DEBUG && warn "new obj: ".Dumper($new);
$DEBUG && warn "    obj: ".Dumper($obj);
$DEBUG && warn "old obj: ".Dumper($old);

# hacky, but there is no namespace descriptor
my $c = $obj->{CLASS};
$c ||= $old->{CLASS};
my $enable = 1 if (($c eq 'System') &&
	!$new->{hostname} && !$new->{domainname}); 

# switch selector based on the event class
if(($c eq 'Network') || $enable) {

	# SET  3 "bootproto" "=" "none" "netmask" "=" "255.255.255.0" 
	#  "ipaddr" "=" "50.60.70.80" "enabled" "=" "1"

	if($old->{ipaddr} || $old->{netmask}) {
		my ($ip, $mask) = ($old->{ipaddr}, $old->{netmask});
		$ip ||= $obj->{ipaddr};
		$mask ||= $obj->{netmask};

		$DEBUG && warn "Remove A/PTR for old NIC config $ip, $mask\n";

		&deletum($cce->find('DnsRecord', {
			'type' => 'PTR',
			'ipaddr' => $ip,
			'netmask' => $mask
			}));
		&deletum($cce->find('DnsRecord', {
			'type' => 'A',
			'hostname' => $system->{hostname},
			'domainname' => $system->{domainname},
			'ipaddr' => $ip,
			}));
	}
	
	# If we're enabling auto-dns we must cycle through all Network objects
	my @nets; 
	if($enable) {
		my @oids = $cce->find('Network');

		foreach my $oid (@oids) {
			my ($ok, $net) = $cce->get($oid);
			push (@nets, $net);
		}
	} else {
		@nets = ($obj);
	}

	foreach my $obj (@nets) {

		# don't redo on nic destruction
		next if (($obj->{ipaddr} && !$obj->{enabled})); 
		# FIXME: replace server identity if it was tied to a 
		#	 deconfigured nic 

		my ($ip, $mask) = ($new->{ipaddr}, $new->{netmask});
		$ip ||= $obj->{ipaddr};
		$mask ||= $obj->{netmask};

		$DEBUG && warn "Create A/PTR for new NIC config $ip, $mask\n";

		next unless ($system->{domainname}); # race condition at the factory

		my @dup = $cce->find('DnsRecord', {
			'type' => 'PTR',
			'hostname' => $system->{hostname},
			'domainname' => $system->{domainname},
			'ipaddr' => $ip,
			'netmask' => $mask
			});
		$cce->create('DnsRecord', {
			'type' => 'PTR',
			'hostname' => $system->{hostname},
			'domainname' => $system->{domainname},
			'ipaddr' => $ip,
			'netmask' => $mask
			}) unless ($dup[0]);

		@dup = $cce->find('DnsRecord', {
			'type' => 'A',
			'hostname' => $system->{hostname},
			'domainname' => $system->{domainname},
			'ipaddr' => $ip,
			'netmask' => $mask
			});
		$cce->create('DnsRecord', {
			'type' => 'A',
			'hostname' => $system->{hostname},
			'domainname' => $system->{domainname},
			'ipaddr' => $ip,
			'netmask' => $mask
			}) unless ($dup[0]);
	}

	# NOTE: we blanket migrate IPs but masks are too generic
	&migratum('DnsRecord', undef, 'ipaddr', $old->{ipaddr}, $new->{ipaddr})
		if($old->{ipaddr} && $new->{ipaddr});
	
	$dirty = 1;

}

if($c eq 'System') {

	# SET  1 "domainname" "=" "cobalt.com" "dns" "=" 
	#  "&10.9.25.254&" "hostname" "=" "lease9"

	# SET  1 . Email "queueTime" "=" "immediate" 
	#  "masqAddress" "=" "" "enableImap" "=" "1" 
	#  "acceptFor" "=" "" "relayFor" "=" "" "enableSMTP" "=" "0" 
	#  "popRelay" "=" "0" "enablePop" "=" "1"
	
	# SET  1 . DNS "auto_config" = 1

	my($host, $domain) = ($old->{hostname}, $old->{domainname});
	$host ||= $system->{hostname};
	$domain ||= $system->{domainname};

	&deletum($cce->find('DnsRecord', {
		'type' => 'MX',
		'hostname' => undef,
		'domainname' => $domain,
		'mail_server_name' => $host.'.'.$domain
		}));

	if($old->{hostname} || $old->{domainname}) {
		my($host, $domain) = ($old->{hostname}, $old->{domainname});
		$host ||= $system->{hostname};
		$domain ||= $system->{domainname};

		$DEBUG && warn "Removing A record on FQDN change\n";

		&deletum($cce->find('DnsRecord', {
			'type' => 'A',
			'hostname' => $host,
			'domainname' => $domain
			}));
		&deletum($cce->find('DnsRecord', {
			'type' => 'A',
			'hostname' => '',
			'domainname' => $domain
			}));
	}

	my($ip, $mask) = &find_netconfig();

	# create the new A record
	$DEBUG && warn "A record management for fqdn change\n";
	my @dup = $cce->find('DnsRecord', {
		'type' => 'A',
		'hostname' => $system->{hostname},
		'domainname' => $system->{domainname},
		'ipaddr' => $ip,
		'netmask' => $mask,
		});
	$cce->create('DnsRecord', {
		'type' => 'A',
		'hostname' => $system->{hostname},
		'domainname' => $system->{domainname},
		'ipaddr' => $ip,
		'netmask' => $mask,
		}) if (!$dup[0] && $system->{domainame});
	@dup = $cce->find('DnsRecord', {
		'type' => 'A',
		'hostname' => '',
		'domainname' => $system->{domainname},
		'ipaddr' => $ip,
		'netmask' => $mask,
		});
	$cce->create('DnsRecord', {
		'type' => 'A',
		'hostname' => '',
		'domainname' => $system->{domainname},
		'ipaddr' => $ip,
		'netmask' => $mask,
		}) if (!$dup[0] && $system->{domainname});

	my ($eok, $mailsys) = $cce->get($sysoid, 'Email');
	if($mailsys->{enableSMTP}) {
		my $fqdn = $system->{hostname}.'.'.$system->{domainname};

		$DEBUG && warn "SMTP service enabled, adding/restoring MX\n";
		$cce->create('DnsRecord', {
			'type' => 'MX',
			'mail_server_priority' => 'high',
			'domainname' => $system->{domainname},
			'mail_server_name' => $fqdn
			});
	}

	# NOTE: we blanket migrate domain but not hostnames as they 
	#       are too generic
	&migratum('DnsRecord', undef, 'domainname', 
		$old->{domainname}, $new->{domainname})
		if($old->{domainname} && $new->{domainname});

	$dirty = 1;
} 

if(($c eq 'DhcpDynamic') || $enable) {
	
	# CREATE  "DhcpDynamic" "ipaddrlo" "=" "10.10.10.50" 
	#  "ipaddrhi" "=" "10.10.10.55"

	# Build hashes to speed our record sorting
	my $prefix = $obj->{prefix};
	my (%ip_to_oid, %name_to_oid, %ip_to_index, %used_index);
	foreach my $oid ($cce->find('DnsRecords')) {
		my($ok, $obj) = $cce->get($oid);
		$ip_to_oid{$obj->{ipaddr}} = $oid;
		$name_to_oid{$obj->{hostname}} = $oid;
		if($obj->{hostname} =~ /^$prefix(\d+)/) {
			my $index = $1;
			$used_index{$index} = 1;
			$ip_to_index{$obj->{ipaddr}} = $index;
		}	
	}

	# FIXME: we delete then recreate the whole range, not efficient
	if($old->{ipaddrlo} || $old->{ipaddrhi}) {
		my($lo, $hi) = ($old->{ipaddrlo}, $old->{ipaddrhi});
		$lo ||= $obj->{ipaddrlo};
		$hi ||= $obj->{ipaddrhi};
		$DEBUG && warn "Delete old Dynamic range $lo, $hi\n";
		foreach my $ip (&ipstep($lo, $hi)) {
			if($ip_to_oid{$ip}) {
				$DEBUG && warn "deleting OID ".$ip_to_oid{$ip}.
					" ip: $ip\n";
				$cce->destroy($ip_to_oid{$ip});
				$used_index{$ip_to_index{$ip}} = 0;
			} 
		}
	}
	
	# If we're enabling auto-dns we must cycle through all ranges 
	my @ranges; 
	if($enable) {
		my @oids = $cce->find('DhcpDynamic');

		foreach my $oid (@oids) {
			my ($ok, $range) = $cce->get($oid);
			push (@ranges, $range);
		}
	} else {
		@ranges = ($obj);
	}

	foreach my $obj (@ranges) { # always single-instance on dhcpd config

		if($new->{ipaddrlo} || $new->{ipaddrhi} || $enable) {
			my($lo, $hi) = ($new->{ipaddrlo}, $new->{ipaddrhi});
			$lo ||= $obj->{ipaddrlo};
			$hi ||= $obj->{ipaddrhi};
			$DEBUG && warn "Create new Dynamic range $lo, $hi\n";
		
			# LAN for dhcpd
			my($ip, $mask) = &find_netconfig('eth0'); 
			
			my $index = 1; 
			foreach my $ip (&ipstep($lo, $hi)) {
				# Fill-in indexed naming holes
				while($used_index{$index}) {
					$index++;
				}
				my @dups = $cce->find('DnsRecord', {
					'type' => 'A',
					'hostname' => $obj->{hostprefix}.$index,
					'domainname' => $system->{domainname},
					});
				$cce->create('DnsRecord', {
					'type' => 'A',
					'hostname' => $obj->{hostprefix}.$index,
					'domainname' => $system->{domainname},
					'ipaddr' => $ip,
					'netmask' => $mask,
					}) unless ($dups[0]);

				@dups = $cce->find('DnsRecord', {
					'type' => 'PTR',
					'ipaddr' => $ip,
					});
				$cce->create('DnsRecord', {
					'type' => 'PTR',
					'hostname' => $obj->{hostprefix}.$index,
					'domainname' => $system->{domainname},
					'ipaddr' => $ip,
					'netmask' => $mask,
					}) unless ($dups[0]);

				$used_index{$index} = 1;
			}
		}
	}
	
	$dirty = 1;
}

if($c eq 'DhcpStatic') {

	# FIXME incomplete support, needs hostname field in dhcpd assignment,
	# 	needs auto_config enable support

	# CREATE  "DhcpStatic" "mac" "=" "11:22:33:44:55:66" 
	#  "ipaddr" "=" "4.5.6.7"
	if($old->{ipaddr}) { 
		$DEBUG && warn "Remove A and PTR for fixed ip: ".$old->{ipaddr}."\n";
	}
	if($new->{ipaddr} || $new->{mac}) {
		my $ip = $new->{ipaddr};
		$ip ||= $obj->{ipaddr};
		$DEBUG && warn "Create A and PTR for fixed IP: $ip\n";
	}

	$dirty = 1;
}

# Now publish our changes
my $time = time(); 
$ok = $cce->set($sysoid, 'DNS', {'commit' => $time}) if ($dirty);

$cce->bye('SUCCESS');
exit 0;



# Subs

sub deletum {
	my @oids = @_;
	foreach my $oid (@oids) { 
		$DEBUG && warn "Deleting OID $oid\n";
		$cce->destroy($oid);
	}
	return 1;
}

sub migratum {
	my ($class, $name, $feature, $old, $new) = @_;

	my @oids = $cce->find('DnsRecord', {$feature => $old});
	foreach my $oid (@oids) {
		$DEBUG && warn "Migrating $class.$name.$feature for OID $oid from $old to $new\n";
		my $ok = $cce->set($oid, $name, {$feature => $new});
		$DEBUG && warn "Set succeeded? $ok\n";
	}
	return 1;
}

sub ipstep {
	my ($lo, $hi) = @_;
	
	# There must be a hundred better ways...  
	my @lo = split(/\./,$lo);
	my @hi = split(/\./,$hi);
	my (@match, @ips);
	my $count = 0;
	my $abort = 256; # Do not return more than a class C or we'll plug
			 # up the DNS records scrollist

	while($lo[0] <= 254) {
		$match[0] = 1 if ($hi[0] == $lo[0]);
		while($lo[1] <= 254) {
			$match[1] = 1 if ($hi[1] == $lo[1]);
			while($lo[2] <= 254) {
				$match[2] = 1 if ($hi[2] == $lo[2]);
				while($lo[3] <= 254) {
					last if($match[0] && $match[1] && 
						$match[2] && ($hi[3] < $lo[3]));
					push(@ips, join('.', @lo));
					$count++;
					$lo[3]++;
					return(@ips) if ($count >= $abort);
				}
				$lo[3] = 1;
				last if ($match[2] && $match[1] && $match[0]);
				$lo[2]++;
			}
			$lo[2] = 1;
			last if ($match[1] && $match[0]);
			$lo[1]++;
		}
		$lo[1] = 1;
		last if ($match[0]);
		$lo[0]++;
	}      

	return(@ips);
}

sub find_netconfig {
	my $nic_bias = shift;

	# Find our public IP address &netmask  eth1 else eth0
	# FIXME what of pppx?  Higher priority than wan port eth1?
	if($nic_bias) {
		my ($ok0, $nic) = $cce->get( 
			($cce->find('Network', {'device' => $nic_bias}))[0] );
		if(($nic->{ipaddr} ne '0.0.0.0') && 
			($nic->{netmask} ne '0.0.0.0') &&
			$nic->{ipaddr} && $nic->{netmask}) {
			return($nic->{ipaddr},  $nic->{netmask});
		}
	} else { 
		foreach my $nic ('eth1', 'eth0') {
			my ($ok, $nic) = $cce->get( 
				($cce->find('Network', {'device' => $nic}))[0]);
			if(($nic->{ipaddr} ne '0.0.0.0') && $nic->{enabled} &&
				($nic->{netmask} ne '0.0.0.0') &&
				$nic->{ipaddr} && $nic->{netmask}) {
				return($nic->{ipaddr},  $nic->{netmask});
			}
		}
	}
	# Note that autodns can be enabled at the factory, localhost settings
	# are appropriate.
	return('127.0.0.1', '255.255.255.0');
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
