#!/usr/bin/perl -w -I/usr/sausalito/perl -I.

# Author: Andrew Bose 
# Copyright 2000, Cobalt Networks.  All rights reserved.
# $Id $
# Changed for Bluapp
# Author: Rickard Osser <rickard.osser@bluapp.com>
# Copyright 2008, Bluapp AB.  All rights reserved.
# $Id $

use strict;
use CCE;
use Sauce::Service;

sub trim($)
{
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}

my $cce = new CCE;
$cce->connectuds();


my ($deviceOid, $systemOid, $ok, $dns, $success, $oid, $return, $device);

if ( -e "/proc/user_beancounters") {
    $device = "venet0:0";
} else {
    $device = "eth0";
}


# check if DhcpParam exists already
my (@oids) = $cce->find("DhcpParam");
if($#oids >= 0) {
    $cce->bye();
    exit 0;
}

if (-e '/etc/dhcpd.conf') {
    unlink('/etc/dhcpd.conf');
}
# Check if DHCP is needed
system("/sbin/dhclient -r -q eth0");
$return = system("/sbin/dhclient -1 -q eth0");

if($return eq "512") {
# DHCP-server is needed, no local DHCP-server
    ($deviceOid) = $cce->find("Network", { "device" => "$device" });
    ($systemOid) = $cce->find("System");
    
    $success = $cce->set($deviceOid, "", { 
	"ipaddr" => "192.168.100.253",
	"netmask" => "255.255.255.0"
	});
    $success = $cce->set($systemOid, "", { 
	"dns" => "&127.0.0.1&",
	"gateway" => "192.168.100.1"
	});

    $success = $cce->create("DhcpParam", {
	netmask => "255.255.255.0",
	gateway => "192.168.100.1",
	dns => "&192.168.100.253&",
	enabled => "0",
	lease => "86400"
	});
    $oid = $cce->oid();
    
    $cce->create("DhcpDynamic", {
	ipaddrlo => "192.168.100.100",
	ipaddrhi => "192.168.100.199"
	});
    
    $cce->set( $oid, "", { 
	'enabled' => "1" 
	});
    Sauce::Service::service_run_init('network', 'restart');
    Sauce::Service::service_run_init('dhcp', 'restart');
} else {
    ($deviceOid) = $cce->find("Network", { "device" => "$device" });
    ($systemOid) = $cce->find("System");
    
    # parse /var/lib/dhclient/dhclient.leases
    my $ip = trim(`grep "fixed-address" /var/lib/dhclient/dhclient.leases | awk -F ' ' '{ print \$2 }' |cut -d ";" -f 1`);
    my $nm = trim(`grep "option subnet-mask" /var/lib/dhclient/dhclient.leases | awk -F ' ' '{ print \$3 }' |cut -d ";" -f 1`);
    my $gateway = trim(`grep "option routers" /var/lib/dhclient/dhclient.leases | awk -F ' ' '{ print \$3 }' |cut -d ";" -f 1`);
    my @dnsServers = `cat /etc/resolv.conf |grep nameserver`;
    $dns = "&";
    foreach my $dnsServer (@dnsServers) {
	($ok, $dnsServer) = split / /,$dnsServer;
	$dnsServer = trim($dnsServer);
	$dns .= "$dnsServer&";
    }
    
    $success = $cce->set($deviceOid, "", { 
	"ipaddr" => "$ip",
	"netmask" => "$nm"
    });
    $success = $cce->set($systemOid, "", { 
	"dns" => "$dns",
	"gateway" => "$gateway"
    });
    
    $success = $cce->create("DhcpParam", {
	enabled => "0",
	lease => "86400"
	});
    $oid = $cce->oid();
    system("/sbin/dhclient -r -q eth0");
    Sauce::Service::service_run_init('network', 'restart');
}

$cce->bye();

# failed?
if(!$success) {
    exit 1;
}

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
