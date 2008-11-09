#!/usr/bin/perl -I /usr/sausalito/perl

use CCE;
my $cce = new CCE;
$cce->connectuds();

my($iterate, $basenet, $domain, $netmask) = @ARGV;
$iterate ||= 80;
$domain ||= 'auto-test.int';
$basenet ||= '172.69.0.0';
$netmask ||= '255.0.0.0';

print "Interate: $iterate\nDomain: $domain\nBasenet: $basenet\n";

my $count = 0;
while ($count < $iterate)
{
	my (@net) = split('\.', $basenet);
	$net[3] = $count+$net[3];
	
	while($net[3] > 255)
	{
		$net[3] -= 255;
		$net[2]++;
	}
	while($net[2] > 255)
	{
		$net[2] -= 255;
		$net[1]++;
	}
	while($net[1] > 255)
	{
		$net[1] -= 255;
		$net[0]++;
	}
	
	my $ip = join('.', @net);
	my $host = "ah$count";

	print "A: $host.$domain, IP: $ip\n";

	my($ok) = $cce->create('DnsRecord', 
		{
		'type' => 'A',
		'domainname' => $domain,
		'hostname' => $host,
		'ipaddr' => $ip,
		'netmask' => $netmask,
		},
		'');
	($ok) = $cce->create('DnsRecord', 
		{
		'type' => 'PTR',
		'domainname' => $domain,
		'hostname' => $host,
		'ipaddr' => $ip,
		'netmask' => $netmask,
		},
		'');
		

	$count++;
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
