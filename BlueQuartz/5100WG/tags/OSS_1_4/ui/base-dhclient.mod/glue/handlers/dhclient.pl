#!/usr/bin/perl -w -I/usr/sausalito/perl/

use strict;
use CCE;
use Sauce::Util;
use Data::Dumper;

my $configFile = "/etc/dhclient.conf";
my $dhclientPid = "/var/run/dhclient.pid";
my $cce = new CCE;

$cce->connectfd();

# note event object is always Network now
my $iface = $cce->event_object();
my ($ok, $dhclient) = $cce->get($cce->event_oid(), "Dhclient");

if (not $ok) {
	$cce->bye('FAIL');
	exit(1);
}

my $startCobalt = "# Start Cobalt Dhclient Config -- $iface->{device}";
my $endCobalt = "# Stop Cobalt Dhclient Config -- $iface->{device}";
my $ethConf=""; #holds interface config
my $count=0;	#timeout counter

# no config if interface is not enabled
if ($iface->{enabled} && $iface->{bootproto} eq 'dhcp' 
	&& ($dhclient->{"hostName"} || $dhclient->{"identifier"})) {
	
	$ethConf=qq(interface "$iface->{device}" {\n);
	$ethConf.=qq(\tsend host-name "$dhclient->{"hostName"}";\n) if $dhclient->{"hostName"};
	$ethConf.=qq(\tsend dhcp-client-identifier "$dhclient->{"identifier"}";\n) if $dhclient->{"identifier"};
	$ethConf.="}\n\n";
}


# rewrite the block in conf file for this interface 

# we need the seprate conf files for seprate dhclient instances
# because of an annoying habit of dhclient's: when you start
# it will look at it's conf file and try to dhcp *all* the 
# interfaces listed in there.  *all* the interfaces, not just
# the ones on the command line.  This causes something on
# the system (I'm betting CCE) to have a fit and block
# dhclient till the handler that spawned it exits, if
# there is more than one instance of dhclient running.
# this means that the /var/state/dhcp/LEASED-<interface>
# file doesn't get touched till after the handler exits
# and that means that the handler will always fail, which
# is bad.
#
# But, so long as each instance only tries to DHCP it's
# assigned interface, everything is ok and nothing has
# a fit.

if(!Sauce::Util::replaceblock($configFile.".".$iface->{device}, $startCobalt, $ethConf, $endCobalt)){
        $cce->warn("[[base-dhclient.cantEditFile]]");
        $cce->bye("FAIL");
	exit 1;
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
