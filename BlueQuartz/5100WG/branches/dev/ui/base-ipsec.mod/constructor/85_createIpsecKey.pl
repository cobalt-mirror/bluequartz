#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use CCE;
use Sauce::Util;
 
my $cce = new CCE();
$cce->connectuds();

system("/sbin/chkconfig ipsec || /sbin/chkconfig --add ipsec");

$keyStuff=`ipsec rsasigkey 1024`;

$keyStuff=~m/pubkey=(.*)/;
$pubkey=$1;

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

$start = "#BEGIN 1024-bit public key.  Auto-generated, do not edit.";
$end =  "#END 1024-bit public key.  Auto-generated, do not edit.";

$top = "$localIps: rsa {\n";
$bottom="\t}\n";
$key=$top.$keyStuff."\n".$bottom;

if(!-e "/etc/freeswan/ipsec.secrets"){
	Sauce::Util::replaceblock(
		"/etc/freeswan/ipsec.secrets",
		$start,
		$key,
		$end,
		0600
	);

	$sysOid=$cce->find("System");
	$cce->set($sysOid, "IpsecRSAKey", {publicKey=>$pubkey});
}

@prof=$cce->find("IpsecProfile", {name=>"Qube3"});
unless(scalar @prof){
	$cce->create("IpsecProfile", 
		{
			enableAutoRsa => "1",
			keylife => "480",
//			replayWindow => "0",
//			manualAuth => "esp",
			comments => "[[base-ipsec.qube3desc]]",
			authValue => "3des-md5-96",
			enableManual => "0",
			auth => "esp",
			rekeymargin => "9",
			keyingtries => "1",
			rekeyfuzz => "100",
			name => "Qube3",
			pfs => "1",
			ikelifetime => "60",
			enableAutoSecret => "1"
		}
	);
}


`ipsec setup restart`;

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
