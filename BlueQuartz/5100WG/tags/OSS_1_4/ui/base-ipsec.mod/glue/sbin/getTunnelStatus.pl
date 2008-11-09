#!/usr/bin/perl -w

my $tunnel = $ARGV[0];
my $start = $ARGV[1];
my $keying = $ARGV[2];
my @info = grep {/$tunnel-(?:nn|hh|hn|nh)/} (`/usr/sbin/ipsec whack --status`);
my %tunnels;
my $upcnt=0;

if($keying eq "manual"){
	#yeah, bad, I know, but pluto doesn't tell us crap 
	#about status for manual tunnels.
	@conns=`grep '$tunnel-' /etc/freeswan/ipsec.conf.tunnels`;
	%tunnels=map {(split/\s/,$_)[-1]=>1} @conns;
	$upcnt=scalar @conns;
	$start="start";
}else{
	foreach(@info){
		/"($tunnel-(?:nn|nh|hn|hh))"/;
		my $t=$1;
		#get up/down info
		#1=up, 0=down
		my $updown=0;
		if(/IPsec SA Established/i){
			$updown=1;
		}
	
		if($updown){
			$upcnt++;
		}
		$tunnels{$t}+=$updown;
	}
}


if($start eq "start"){
	#we are supposed to start the connection.
	#therefore if the tunnel is not up we have
	#a problem.

	if($upcnt==0){
		print "severeProblem\n";
	}else{
		#try a ping here?
		print "normal\n";
		print join " ", (map {(split/-/)[-1]} (grep {$tunnels{$_}==1} keys %tunnels));
		#-1 == last element
		print "\n";
	}
}else{
	#the other end is supposed to start the connection.
	#therefore we only have a problem if the connection
	#profile(s) are not in ipsec's memory.

	if(scalar keys %tunnels >= 1){
		print "normal\n";
		print join " ", (map {(split/-/)[-1]} (grep {$tunnels{$_}==1} keys %tunnels));
                #-1 == last element
                print "\n";
	}else{
		print "severeProblem\n";
		print "[[base-ipsec.missingTunnel]]\n";
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
