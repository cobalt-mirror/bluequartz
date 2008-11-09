#!/usr/bin/perl -w -I/usr/sausalito/perl -I.

# Copyright 2000, Cobalt Networks.  All rights reserved.
# $Id: 30_addNetwork.pl 3 2003-07-17 15:19:15Z will $
# author: jmayer@cobalt.com

use strict;
use CCE;

my $errors = 0;

my $cce = new CCE;
$cce->connectuds();

# count devices:
my @devices = qw/ eth0 eth1 /;
my $device;
foreach $device (@devices) {
	# parse ifconfig
	my $ip = ""; my $nm = "";
	my $data = join("", `/sbin/ifconfig $device`);
	if ($data =~ m/^$device/s) {
		if ($data =~ m/inet addr:\s*(\S+)/s) {
			$ip = $1;
		}
		if ($data =~ m/Mask:\s*(\S+)/s) {
			$nm = $1;
		}
	}


	my @oids = $cce->find("Network", { 'device' => $device } );

	my $oid = 0;
	if ($#oids == 0) {
		$oid = $oids[0];
		# all good
	} elsif ($#oids < 0) {
		# create one:
		my $obj = { 'device' => $device };
		if ($ip && $nm) {
			print STDERR "Using old config: $ip/$nm\n";
			$obj->{ipaddr} = $ip;
			$obj->{netmask} = $nm;
			$obj->{enabled} = 1;
		} else {
			$obj->{enabled} = &onboot($device);
		}
		my ($success) = $cce->create("Network", $obj );
		if (!$success) {
			print STDERR "Failed to create Network.$device object\n"; 
			$errors++;
		} else {
			print STDERR "Created Network.$device object.\n";
		}
		# turn on NAT and IPForwarding
		hack_on_nat();
		# update MAC address
		$oid = $cce->oid();
	} else {
		# destroy extras
		shift(@oids);
		foreach $_ (@oids) {
			my ($success) = $cce->destroy($_);
			if ($success) {
				print STDERR "Destroyed surplus Network.$device object $_\n";
			} else {
				print STDERR "Failed to destroy surplus Network.$device object $_\n";
				$errors++;
			}
		}
	}
	if ($oid) {
		$cce->set($oid, "", { "refresh" => scalar(time()) });
	}
}

$cce->bye();
exit($errors);

sub hack_on_nat
{
	my (@oids) = $cce->find("System");
	my ($oid) = shift(@oids);
	if ($oid) {
		$cce->set($oid, "Network", { 
			'nat' => '1',
			'ipForwarding' => '1',
		});
		# debugging:
		system("/bin/cp",
			'/etc/sysconfig/network',
			'/tmp/.network.' . scalar(time()) );
	}
}
	
# check if ifcfg has onboot flag set to yes
# if so returns 1, else returns 0
sub onboot
{
	my $device = shift;

	my $onboot = 0;

	if(open(IFCFG, "/etc/sysconfig/network-scripts/ifcfg-$device")) {
		while(<IFCFG>) {
			if(/^ONBOOT=(\w+)$/i) {
				if ($1 =~ /yes/i) {
					$onboot = 1;
				}
			}
		}

		close IFCFG;
	}

	return $onboot;
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
