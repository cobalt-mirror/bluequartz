#!/usr/bin/perl -w -I/usr/sausalito/perl

use CCE;
use strict;
use Data::Dumper;

my $cce = new CCE();

$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object;
(!$obj) && $cce->warn("[[base-dhcpd.cceError]]");

print STDERR "$0: Checking Device name..\n";
# check to see if we are on eth0
if ($obj->{device} ne "eth0") {
	$cce->bye("SUCCESS");
	exit();
}

print STDERR "$0: Checking If DHCP is enabled..\n";
# check to see if dhcpd is in fact enabled
{
	my @dhcpParamOid = $cce->find("DhcpParam");
	print STDERR Dumper(@dhcpParamOid);
	(!@dhcpParamOid) && $cce->warn("[[base-dhcpd.cceError");
	my ($ok, $dhcpParam) = $cce->get($dhcpParamOid[0]);
	if (!($dhcpParam->{enabled})) {
		$cce->bye("SUCCESS");
		exit;
	}
}

print STDERR "$0: Grabbing the system\n";
my @sysOid = $cce->find("System");
(!@sysOid) && $cce->warn("[[base-dhcpd.cceError]]");


my ($ok, $system) = $cce->get($sysOid[0]); 
(!$ok) && $cce->warn("[[base-dhcpd.cceError]]");

# this is the running netmask
my $settingsNetmask = 0xFFFFFFFF;

my (@oids) = $cce->find("DhcpDynamic");
foreach my $oid (@oids) {
	my ($ok, $dynamic) = $cce->get($oid);
	(!$ok) && $cce->warn("[[base-dhcpd.cceError]]");
	my $lo = ip2bin($dynamic->{ipaddrlo});
	my $hi = ip2bin($dynamic->{ipaddrhi});
	$settingsNetmask = $settingsNetmask & $lo & $hi;
}	

@oids = $cce->find("DhcpStatic");
foreach my $oid (@oids) {
	my ($ok, $static) = $cce->get($oid);
	(!$ok) && $cce->warn("[[base-dhcpd.cceError]]");
	my $ip = ip2bin($static->{ipaddr});
	$settingsNetmask = $settingsNetmask & $ip;
}

# get the current Ip
my $curIp = ip2bin($obj->{ipaddr});
my $netmask = ip2bin($obj->{netmask});

print STDERR "Netmask: $netmask\n" .
		"CurIp: $curIp\n" .
		"SettingNetmask: $settingsNetmask\n";

if (($curIp & $netmask) != ($settingsNetmask & $netmask)) {
	# Something went wrong..
	# Turn off the dhcp service and tell the user via info 
	# This is done this way as changing the IP of a machine via LCD
	#   would become difficult if the dhcpd service was enabled
	my @dhcpParamOid = $cce->find("DhcpParam");
	(!$dhcpParamOid[0]) && $cce->warn("[[base-dhcpd.cceError]]");
	
	my ($ok, @otherInfo) = $cce->set($dhcpParamOid[0], "", {enabled => ""});
	if ($ok) {
		$cce->info("[[base-dhcpd.turnedOffDhcpdIpChange]]");
		$cce->bye("SUCCESS");
	} else {
		$cce->warn("[[base-dhcpd.errorWhileDisablingDhcpdIpChange]]");
		$cce->bye("FAIL");
	}
	
}

$cce->bye("SUCCESS");
exit(0);

sub ip2bin
{
  return unpack("N",pack("C4",split(/\./, shift)));
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
