#!/usr/bin/perl
# $Id: deldns.pl 922 2003-07-17 15:22:40Z will $


use lib "/usr/sausalito/perl";
use CCE;
use Data::Dumper;

$cce = new CCE;
$cce->connectuds();

destoryDnsRecords();
destoryDnsSlave();
destoryDnsSOA();

$cce->bye("bye");

exit 0;

sub destoryDnsSOA
{
	print "Destorying DNS SOA records\n";
	my @oids = $cce->find("DnsSOA");

	print "SOA oids are: ", @oids, "\n";
	my ($ok, $obj);
	foreach my $oid (@oids) {
		($ok, $obj) = $cce->destroy($oid);
	}
}

sub destoryDnsSlave
{
	print "Destorying DNS Slave records\n";
	my @oids = $cce->find("DnsSlaveZone");

	print "Slave oids are: ", @oids, "\n";
	my ($ok, $obj);
	foreach my $oid (@oids) {
		($ok, $obj) = $cce->destroy($oid);
	}
}


sub destoryDnsRecords
{
	print "Destorying DNS Records (a,mx,cname)\n";
	my @oids = $cce->find("DnsRecord");

	print "Records oids are: ", @oids, "\n";
	my ($ok, $obj);
	foreach my $oid (@oids) {
		($ok, $obj) = $cce->destroy($oid);
	}
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
