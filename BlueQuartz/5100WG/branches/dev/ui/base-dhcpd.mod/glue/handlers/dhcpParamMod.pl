#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/dhcpd
#$Id: dhcpParamMod.pl 201 2003-07-18 19:11:07Z will $
#
# DhcpParam.* handler
# author: Andrew Bose <andrew@cobalt.com>
# based off of Will DeHaan's Dhcp.pm
#
# generates the following i18n messages:

#use strict;
use Sauce::Config;
use FileHandle;
use CCE;
use Dhcpd;

my $cce = new CCE;
$cce->connectfd(\*STDIN,\*STDOUT);


# get system object id
my ($system_oid) = $cce->find("System");
# get system object:
my ($ok, $obj) = $cce->get($system_oid);
if (!$ok) {
  $cce->warn("[[base-dhcpd.noSystemObj]]");
  $cce->bye('FAIL');
}

# get network object id
my ($network_oid) = $cce->find("Network", {device => "eth0"});
# get network object:
my ($okn, $netobj) = $cce->get($network_oid);
if (!$okn) {
  $cce->warn("[[base-dhcpd.noNetworkObj]]");
  $cce->bye('FAIL');
}

# get dhcpparam object id
my ($param_oid) = $cce->find("DhcpParam");
# get dhcpparam object:
my ($jok, $paramobj) = $cce->get($param_oid);
if (!$jok) {
   # this may be ok, since this may be first boot and the object does not exist 
   #  $cce->bye('FAIL', '[[base-dhcpd.noParamObj]]');
}

# check if dhcp is being enabled or disabled, if being disabled just go away gracefully
# otherwise if you auto-config network, then reset network, and try to manually enter an
# ip on a different network than 10.6.18 dhcp dhcpIpChange will correctly try to disable
# dhcpd, but it will most likely fail due to the test below that checks to see if the gateway
# is on the same subnet as the primary interface ip
if (exists($paramobj->{enabled}) && not $paramobj->{enabled}) {
	# we're being disabled we don't need to check if the network settings are okay
	$cce->bye('SUCCESS');
	exit 0;
}

my $dns = $paramobj->{dns};
my $domain = $paramobj->{domainname};
my $gw = $paramobj->{gateway};
my $nm = $paramobj->{netmask};
my $maxlease = $paramobj->{lease};

# give defaults in case something is not setup yet....
$domain = $obj->{domainname} unless $domain;
$gw = $netobj->{ipaddr} unless $gw;
$nm = $netobj->{netmask} unless $nm;
my ($subnet, $broadcast) = calcnetwork ($gw, $nm);
$dns = "" unless $dns;

my @dns = $cce->scalar_to_array($dns);


# Make sure the Gateway is on the same subnet as the Primary Interface
unless (Dhcpd::net_network_ismember($gw,$netobj->{ipaddr},$nm)) {
            $cce->warn("[[base-dhcpd.gwMismatch]]");
	    $cce->bye('FAIL');
	    exit 1;
}


my $err=Dhcpd::dhcpd_set_parameters( $domain, $gw, $nm, $maxlease, $broadcast, @dns );

if ($err) {
    $cce->warn("[[base-dhcpd.cantSetParam]]");
    $cce->bye('FAIL');
    exit 1;
}

$cce->bye('SUCCESS');
exit(0);


####################### helper functions ##############################

sub calcnetwork
{
        my ($ipaddr, $netmask) = (shift, shift);
  my $binip = pack("CCCC", split(/\./, $ipaddr));
  my $binmask = pack("CCCC", split(/\./, $netmask));
  my $binnet = $binip & $binmask;
  my $binbcast = $binnet | ~$binmask;
  my $network = join(".",unpack("CCCC", $binnet));
  my $bcast   = join(".",unpack("CCCC", $binbcast));
  return ($network, $bcast);
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
