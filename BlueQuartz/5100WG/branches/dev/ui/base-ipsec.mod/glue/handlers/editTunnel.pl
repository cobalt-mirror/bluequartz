#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $DEBUG = 0;

if($DEBUG){
	open D, "+>/tmp/editTunnel.pl.$$";
	print D scalar localtime;
	print D "\n";
}

my $obj = $cce->event_object();

my $tunnelName=$obj->{name};
$tunnelName =~ s/\s/_/g;

if($obj->{remoteSubnet} ne "" && $obj->{remoteNetmask} ne ""){
	$rightsubnet = "$obj->{remoteSubnet}/$obj->{remoteNetmask}";
}else{
	$rightsubnet="";
}

$profileName = $obj->{profile};
$profileName =~ s/\s/_/g;

my $variableStuff;
my $start;
my %spis;
#my $spibase;

if($obj->{keying} eq "manual"){
	$profileName.="-manual";

	$obj->{spi}=~/^(0x[0-9a-z]+)([0-9a-z])$/i;
	my $base=$1;
	my $i=$2;
	%spis=(
		hh=>$base.$i++, 
		hn=>$base.$i++, 
		nh=>$base.$i++, 
		nn=>$base.$i
	);

	$auto=$obj->{enabled};

	if($obj->{encKey}){
		$variableStuff .= "espenckey=$obj->{encKey}\n";
		$variableStuff .= "\tespauthkey=$obj->{authKey}\n";
	}else{
                $variableStuff .= "ahkey=$obj->{authKey}\n";
	}
}else{
	my $soid=$cce->find("System");
	my $ipsec_sys;
	(undef,$ipsec_sys)=$cce->get($soid, "IpsecRSAKey");
	$profileName.="-auto";
	$authby=$obj->{keying} eq "autoRsa"?"rsasig":"secret";
	$auto=$obj->{enabled};

	$start="auto=$auto";

	$variableStuff.= "authby=$authby\n";
	if($obj->{keying} eq "autoRsa"){
		$variableStuff.="\tleftrsasigkey=$ipsec_sys->{publicKey}\n";
		$variableStuff.="\trightrsasigkey=$obj->{authKey}\n";
	}
}

chomp $variableStuff;

print D "profile name: $profileName\n" if $DEBUG;

@oid = $cce->find("Network", {enabled=>1});
my $eth0;
foreach(@oid){
 	my $if;
	(undef, $if)=$cce->get($_);
	if($if->{device} eq "eth0"){
		$eth0=$if;
	}
}
$subnet = getSubnet($eth0->{ipaddr}, $eth0->{netmask});
$subnet.="/$eth0->{netmask}";

$startString = "# BEGIN auto-generated tunnel $obj->{name}.  DO NOT EDIT";
$endString = "# END auto-generated tunnel $obj->{name}.  DO NOT EDIT";

$hn = << "EOF";
conn $tunnelName-hn
        also=$tunnelName
        $start
        leftsubnet=
        rightsubnet=$rightsubnet
	spi=$spis{hn}

EOF

$nn = <<"EOF";
conn $tunnelName-nn
        also=$tunnelName
        $start
        leftsubnet=$subnet
        rightsubnet=$rightsubnet
	spi=$spis{nn}

EOF

$hh = << "EOF";
conn $tunnelName-hh
        also=$tunnelName
        $start
        leftsubnet=
        rightsubnet=
	spi=$spis{hh}
 
EOF

$nh = << "EOF";
conn $tunnelName-nh
        also=$tunnelName
        $start
        rightsubnet=
        leftsubnet=$subnet
	spi=$spis{nh}

EOF

$base = << "EOF";
conn $tunnelName
        also=$profileName
        $variableStuff
        left=%defaultroute
        leftnexthop=
	leftupdown=/usr/sausalito/sbin/ipsec.pl
        right=$obj->{remoteIp}
        rightnexthop=$obj->{remoteGatewayIp}

EOF

$tunnelString = $hh;
$tunnelString .= $hn if $rightsubnet ne ""; #if there is a right subnet
$tunnelString .= $nh if scalar @oid > 1; #if we have more than 1 interface
$tunnelString .= $nn if scalar @oid > 1 && $rightsubnet ne "";
$tunnelString .= $base;

print D "tunnel string:\n" if $DEBUG;
print D $tunnelString if $DEBUG;

Sauce::Util::replaceblock(
	"/etc/freeswan/ipsec.conf.tunnels",
	$startString,
	$tunnelString,
	$endString,
	0600
);

$cce->bye("SUCCESS");

if($obj->{keying} ne "manual"){
	print D "keying != manual\n" if $DEBUG;
	if($cce->event_is_create()){
		if($auto ne "ignore"){
			my $d="";
			print D "create\n" if $DEBUG;
			$d.=`ipsec auto --add $tunnelName-$_` foreach(qw/nn hh hn nh/);
			print D $d if $DEBUG;
			if($auto eq "start"){
				my $d="";
				print D "auto=start\n" if $DEBUG;
				$d.=`ipsec auto --up $tunnelName-$_` foreach(qw/nn hh hn nh/);
				print D $d if $DEBUG;
			}
		}
	}else{
		if($auto eq "ignore"){
			`ipsec auto --delete $tunnelName-$_` foreach(qw/nn hh hn nh/);
		}else{
			my $d="";
			print D "not create\n" if $DEBUG;
			$d.=`ipsec auto --replace $tunnelName-$_` foreach(qw/nn hh hn nh/);
			print D $d if $DEBUG;
        	        if($auto eq "start"){
				my $d="";
				print D "auto=start\n" if $DEBUG;
	                        $d.=`ipsec auto --up $tunnelName-$_` foreach(qw/nn hh hn nh/);
				print D $d if $DEBUG;
                	}
		}
	}
}else{
	print D "keying == manual\n" if $DEBUG;
	if($auto eq "ignore"){
		print D "down\n" if $DEBUG;
		if(!$cce->event_is_create()){
			my $d;
			$d.=`ipsec manual --down $tunnelName-$_` foreach(qw/nn hh hn nh/);
		        print D $d if $DEBUG;
		}
	}else{
		print D "up\n" if $DEBUG;
		my $d;
		$d.=`ipsec manual --up $tunnelName-$_` foreach(qw/nn hh hn nh/);
		print D $d if $DEBUG;
	}
}

$cce->bye("SUCCESS");
exit(0);


sub getSubnet{
    my ($addr,$netmask) = @_;
    my ($a1,$a2,$a3,$a4) = map(pack('C',$_),split(/\./o,$addr));
    my ($m1,$m2,$m3,$m4) = map(pack('C',$_),split(/\./o,$netmask));
    my ($n1,$n2,$n3,$n4,$network);
    $n1 = unpack('C',($m1 & $a1));
    $n2 = unpack('C',($m2 & $a2));
    $n3 = unpack('C',($a3 & $m3));
    $n4 = unpack('C',($a4 & $m4));
    $network = "$n1.$n2.$n3.$n4";
    return $network; 
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
