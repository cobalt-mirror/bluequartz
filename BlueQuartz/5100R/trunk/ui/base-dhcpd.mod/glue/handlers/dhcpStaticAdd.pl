#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/dhcpd
#$Id: dhcpStaticAdd.pl,v 1.1.1.1 2003/07/17 15:15:49 will Exp $
#
# DhcpStatic._CREATE handler
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

# retreive user object data:
my $oid = $cce->event_oid();

if (!$oid) {
        # something is very, very wrong.
  $cce->bye('FAIL', 'Bad CSCP header');
  exit(1);
}

my $paramobj = $cce->event_object();
use Data::Dumper;
print STDERR Dumper($paramobj),"\n";

my $ipAddr = $paramobj->{ipaddr};
my $mac = $paramobj->{mac};
$mac=uc( $mac );


# get network object id
my ($network_oid) = $cce->find("Network", {device => "eth0"});
# get network object:
my ($okn, $netobj) = $cce->get($network_oid);
if (!$okn) {
  $cce->warn("[[base-dhcpd.noNetworkObj]]");
  $cce->bye('FAIL');
}

print STDERR "$0: adding static range ",$ipAddr, " ", $mac,"\n";

# Make sure the IP is on the same subnet as the Primary Interface
unless (Dhcpd::net_network_ismember($netobj->{ipaddr},$ipAddr,$netobj->{netmask})) {
            $cce->warn("[[base-dhcpd.ipMismatch]]");
            $cce->bye('FAIL');
            exit 1;
}

    # get current list
    my %macs=Dhcpd::dhcpd_get_macs();
    if( $macs{ $mac } ) {
	$cce->warn("[[base-dhcpd.macAddrAlreadySpecified]]");
	$cce->bye('FAIL');
	exit;
    }
    else {
        # everything is fine
        # add static assignment
        $macs{ $mac }=$ipAddr;

        # create conf file if it does not exist
        Dhcpd::dhcpd_create_conf();

	# get see if any Dynamic Ranges have been created 
	# create a null range if not..
	my ($dynam_oid) = $cce->find("DhcpDynamic");
	if (!$dynam_oid) {
		Dhcpd::dhcpd_set_range();
	}

        my $ret=Dhcpd::dhcpd_set_macs( %macs );
        if ($ret) {
                $cce->warn("$ret");
                $cce->bye('FAIL');
                exit;
            }

    }



$cce->bye('SUCCESS');
exit(0);



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
