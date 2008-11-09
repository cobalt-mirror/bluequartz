#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use CCE;

my $cce = new CCE;
$cce->connectfd();

my $DEBUG=0;

if($DEBUG){
	open D, "/tmp/restart.pl.$$";
	print D scalar localtime;
}


my $profile = $cce->event_object();
my $error  = 0;

my @oids = $cce->find("IpsecTunnel", {profile=>$profile->{name}});

print D "oids:  " if $DEBUG;
print D join ",", @oids if $DEBUG;

foreach(@oids){
	my $tunnel;
	(undef,$tunnel) = $cce->get($_);
	$keying = $tunnel->{keying} eq "manual"?"manual":"auto";
	$tname = $tunnel->{name};
	$tname =~ s/\s/_/g;

	if($DEBUG){
		print D "$_:\n";
		print D "name: $tunnel->{name}\n";
		print D "keying: $keying\n";
	}

	if($keying eq "auto"){
		print D "restarting, auto\n" if $DEBUG;
		if($obj->{enabled} ne "ignored"){
			print D "not ignored\n" if $DEBUG;
			my $t=`ipsec auto --reload $tname-$_` foreach(qw/nn hh hn nh/);
			print D $t if $DEBUG;
			if($obj->{enabled} eq "start"){
				print D "bringing it up...\n" if $DEBUG;
				my $t=`ipsec auto --up $tname-$_` foreach(qw/nn hh hn nh/);
				print D $t if $DEBUG;
			}
		}
	}else{
		print D "restarting, manual" if $DEBUG;
		if($obj->{enabled} ne "ignore"){
			print D "not ignored\n" if $DEBUG;
			my $t;
			$t=`ipsec manual --down $tname-$_` foreach(qw/nn hh hn nh/);
			$t.=`ipsec manual --unroute $tname-$_` foreach(qw/nn hh hn nh/);
			$t.=`ipsec manual --up $tname-$_` foreach(qw/nn hh hn nh/);
			print D $t if $DEBUG;
		}
	}
	print D "\n" if $DEBUG;
}

close D if $DEBUG;

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
