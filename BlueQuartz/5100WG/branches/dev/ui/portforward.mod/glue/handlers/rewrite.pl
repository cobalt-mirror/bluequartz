#!/usr/bin/perl -I/usr/sausalito/perl

# $id:$ 

use CCE;
use Sauce::Util;
use strict;

my $portForwardRulesFile = "/etc/portfw.conf";

my $cce = new CCE();
$cce->connectfd(\*STDIN, \*STDOUT);

my $data; my $ok;
$data = "ipmasqadm portfw -f\n";

# get the network object for devicename to ipaddr translation
my @networkOids = $cce->find("Network");
my $networks = {};
for my $networkOid (@networkOids) {
	my ($ok, $network) = $cce->get($networkOid);
	$networks->{$network->{device}} = $network;
}

my @oids = $cce->find("PortForward");
for my $oid (@oids) {
	my ($ok, $obj) = $cce->get($oid);
	#grab the ip
	my $ipaddr = $networks->{$obj->{sourceIP}}->{ipaddr};
	#write the line
	$data .= "ipmasqadm portfw -a -P " . lc ($obj->{protocol}) . " -L " . $ipaddr . " " . $obj->{sourcePort} . " -R " . $obj->{targetIP} . " " . $obj->{targetPort} . "\n";
}

system("touch $portForwardRulesFile 2> /dev/null");
system("chmod 0700 $portForwardRulesFile");

Sauce::Util::editfile( $portForwardRulesFile, \&writeConfig, $data);

system("/etc/rc.d/init.d/portfw restart 2> /dev/null");

$cce->bye("SUCCESS");
1;

sub writeConfig {
	my $fin = shift;
	my $fout = shift;
	my $data = shift;
	print $fout ($data);
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
