#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: validate.pl 201 2003-07-18 19:11:07Z will $

use CCE;
use strict;

# Declare variables used globally.
my ($cce,$cur_oid,$cur_obj,$ok,@oids,@routeTable);

# Setup $cce, get object
$cce = new CCE("Domain" => "base-portforward");
$cce->connectfd(\*STDIN, \*STDOUT);
$cur_oid = $cce->event_oid();
$cur_obj = $cce->event_object();
@oids = $cce->find("PortForward");

### Check if the new source IP & port already exist in another rule ###
for my $oid (@oids) {
	if ($oid ne $cur_oid) {
		my ($ok, $obj) = $cce->get($oid);
		if
		(
			$obj->{sourceIP} eq $cur_obj->{sourceIP} &&
			$obj->{sourcePort} eq $cur_obj->{sourcePort} &&
			$obj->{protocol} eq $cur_obj->{protocol}
		) {
			$cce->warn("collidingAddresses",
				{
					"ip" => "$cur_obj->{sourceIP}",
					"port" => "$cur_obj->{sourcePort}",
				},
			);
			$cce->bye("FAIL");
			exit;
		}		# endif
	}
}

### Check that the dest & source IPs are on different interfaces
if (!(check_interfaces())) {
	$cce->warn("[[base-portforward.mustCrossInterfaces]]");
	$cce->bye("FAIL");
	exit;
}

$cce->bye("SUCCESS");
1;



### Subroutines Below ###
# Checks if the $cur_obj{"targetIP"} will be routed over the same
# interface as $cur_obj{"sourceIP"}.  Yes, I like global vars.
# Returns 1 on success, 0 on failure.
sub check_interfaces {
	buildRoutingTable();		# builds global @routeTable
	#
	# If the sourceIP (i.e. eth0) is eq to the new targetIP then
	# the user is trying to create a rule to forward from/to the same
	# interface.  Puke if so.
	#
	if ( ($cur_obj->{"sourceIP"}) eq
			(get_route_iface($cur_obj->{"targetIP"}))) {
		return 0
	} else {
		return 1
	}
}

### buildRoutingTable()
# Build a 'routing table' useful for our purposes here.  This table
# is an array of anonymous hashes.  Looks like:
# @array = (	{
#			'interface'	=> 'eth0',
#			'address'	=> '192.168.0.1',
#			'netmask'	=> '255.255.0.0',
#		},
# )
# This table specifies all the ip/netmask combinations and which interface
# the traffic will go through that CCE knows about.  Created by combining
# CCE's Network.*, Route.*, and entries.
# Uses global @routeTable
#
sub buildRoutingTable {
	my (@Oids,$oid,$ok,$obj);

	# Takes interface, address, netmask; pushes them onto @table
	sub pushOnTable {
		push(@routeTable,	{
					"interface"	=> $_[0],
					"address"	=> $_[1],
					"netmask"	=> $_[2]
				},
		);
	}

	# Get Network Devices
	@Oids = $cce->find("Network");
	foreach $oid (@Oids) {
		($ok, $obj) = $cce->get($oid);
		if ( ($ok) && ($obj->{"enabled"}) ) {
			pushOnTable(	$obj->{"device"},
					$obj->{"ipaddr"},
					$obj->{"netmask"}	
			);
		}
	}
	# Get Route Devices
	@Oids = $cce->find("Route");
	foreach $oid (@Oids) {
		($ok, $obj) = $cce->get($oid);
		if ($ok) {
			pushOnTable(	$obj->{"device"},
					$obj->{"target"},
					$obj->{"netmask"}	
			);
		}
	}

	# Get Default Gateway. This is so annoying!
	my($gtwyIP,$gtwyDevice,$gtwyMask,@gtwyDeviceOids);
	# Get the System.gateway
	$gtwyIP = get_default_gateway();
	# Get the device which the System.gateway will route onto. (eth[0,1])
	$gtwyDevice = get_route_iface($gtwyIP);
	# Now, get the netmask for the Network object that the
	# default gateway routes through.
	@gtwyDeviceOids = $cce->find("Network", { 'device' => $gtwyDevice });
	my($gtwyOk,$gtwyObj) = $cce->get($gtwyDeviceOids[0]);
	$gtwyMask = $gtwyObj->{"netmask"};
	pushOnTable($gtwyDevice,$gtwyIP,$gtwyMask);
}

# returns iface name ("eth0" or "eth1") that IP would route onto or 0 if unknown
# IPAddr refers to parameter passed, NetAddr refers to IP Address of the
# current device or route we are considering.  NetMask is the mask of
# device/route.  Relies upon global @networks to be setup properly.
sub get_route_iface {
	my($binIPAddr,$binNetAddr,$binNetMask,$netDevice,$i);
	$netDevice = 0;		# value returned if we don't find an interface
	$binIPAddr = inet_aton(shift()) || die "You must pass an ip address!\n";
	for (my $i = 0; $i < scalar(@routeTable); $i++) {
		$binNetAddr = inet_aton($routeTable[$i]{"address"});
		$binNetMask = inet_aton($routeTable[$i]{"netmask"});
		###
		# If the (binNetAddr & binNetMask) eq (binIPAddr & binNetMask)
		# then this IPAddr would be routed over the device at
		# $networks[$i]{"interface"}.  Do sting comparison (eq)
		# on the results because they are packed strings, not nums
		###
		if (($binNetAddr & $binNetMask) eq ($binIPAddr & $binNetMask)) {
			$netDevice = $routeTable[$i]{"interface"};
		}
	}
	return $netDevice;
}

# Returns IP address of default gateway
sub get_default_gateway {
	my(@Oids,$ok,$obj);
	@Oids = $cce->find("System");
	($ok,$obj) = $cce->get($Oids[0]);
	return $obj->{"gateway"}
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
