#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $obj = $cce->event_object();

#get eth0
@oids=$cce->find("Network", {device=>"eth0"});
(undef, $eth0)=$cce->get($oids[0]);
$localIps=$eth0->{ipaddr};

undef @oids;
#get eth1
@oids=$cce->find("Network", {device=>"eth1", enabled=>1});
if(scalar @oids){
	(undef, $eth1)=$cce->get($oids[0]);
	$localIps .= " $eth1->{ipaddr}";
}

if($obj->{CLASS} eq "Network"){
	#replace PSK ips
	@tunnels=$cce->find("IpsecTunnel", {keying=>"autoSecret"});
	foreach $oid (@tunnels){
		my $tunnel=$cce->get($oid);
		my $startString="#start pre-shared key for '$tunnel->{name}'.  DO NOT EDIT";
		my $secret="$localIps $tunnel->{remoteIp}: PSK \"$tunnel->{authKey}\"\n";
		my $endString="#end pre-shared key for '$tunnel->{name}'.  DO NOT EDIT";
 
                Sauce::Util::replaceblock(
                        "/etc/freeswan/ipsec.secrets",
                        $startString,
                        $secret,
                        $endString,
                        0600
                );
	}

	#replace rsa ip
	open IN, "/etc/freeswan/ipsec.secrets";
	open OUT, "+>/etc/freeswan/ipsec.secrets~";
	while(<IN>){
		s/[0-9\.\s]+: rsa/$localIps: rsa/;
		print OUT;
	}
	close OUT;
	close IN;
	system("cp /etc/freeswan/ipsec.secrets~ /etc/freeswan/ipsec.secrets");


}else{	#CLASS=IpsecTunnel

	if($obj->{keying} eq "autoSecret"){
	
		$startString="#start pre-shared key for '$obj->{name}'.  DO NOT EDIT";
		$secret="$localIps $obj->{remoteIp}: PSK \"$obj->{authKey}\"\n";
		$endString="#end pre-shared key for '$obj->{name}'.  DO NOT EDIT";

		Sauce::Util::replaceblock(
			"/etc/freeswan/ipsec.secrets",
			$startString,
			$secret,
			$endString,
			0600
		);

	}
}

`ipsec auto --rereadsecrets`;

$cce->bye("SUCCESS");
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
