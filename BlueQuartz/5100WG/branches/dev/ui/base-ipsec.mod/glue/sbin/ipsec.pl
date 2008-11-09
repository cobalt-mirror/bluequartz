#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use Sauce::Util;

$_ = $ENV{PLUTO_VERB};

/prepare-(?:host|client)/ && prepare() ||
/((?:un)?route)-(?:host|client)/ && route($1) ||
/(up|down)-(host|client)/ && updown($1, $2) ||

exit(0);


sub prepare{
	#translate prepare-(host|client) from _updown
	if("$ENV{PLUTO_PEER_CLIENT_NET}/$ENV{PLUTO_PEER_CLIENT_MASK}" eq "0.0.0.0/0.0.0.0"){
                # horrible kludge for obscure routing bug with opportunistic
                $parms1="-net 0.0.0.0 netmask 128.0.0.0";
                $parms2="-net 128.0.0.0 netmask 128.0.0.0";
                system("route del $parms1 2>&1 ; route del $parms2 2>&1");
	}else{
                $parms="-net $ENV{PLUTO_PEER_CLIENT_NET} netmask $ENV{PLUTO_PEER_CLIENT_MASK}";
                system("route del $parms 2>&1");
	}
}

sub route{
	my $action = $_[0] eq "route"?"add":"del";
	#routing stuff here from _updown
        $parms="-net $ENV{PLUTO_PEER_CLIENT_NET} netmask $ENV{PLUTO_PEER_CLIENT_MASK}";
        $parms2="dev $ENV{PLUTO_INTERFACE} gw $ENV{PLUTO_NEXT_HOP}";

	if("$ENV{PLUTO_PEER_CLIENT_NET}/$ENV{PLUTO_PEER_CLIENT_MASK}" eq "0.0.0.0/0.0.0.0"){
                # horrible kludge for obscure routing bug with opportunistic
                system("route $action -net 0.0.0.0 netmask 128.0.0.0 $parms2 &&
                        route $action -net 128.0.0.0 netmask 128.0.0.0 $parms2");
	}else{
		system("route $action $parms $parms2");
	}
}

sub updown{
	my($action, $target) = @_;
	#do firewall stuff here
	my $command="";

	my $start = "#begin rule for ipsec connection $ENV{PLUTO_CONNECTION}";
	my $end = "#end rule for ipsec connection $ENV{PLUTO_CONNECTION}";

	if($target eq "client"){
		#client stuff
		if($action eq "up"){
			#up stuff
			system("ipchains -I forward -b -s $ENV{PLUTO_MY_CLIENT_NET}/$ENV{PLUTO_MY_CLIENT_MASK} -d $ENV{PLUTO_PEER_CLIENT_NET}/$ENV{PLUTO_PEER_CLIENT_MASK} -j ACCEPT");
			$command="ipchains -I 2 forward -b -s $ENV{PLUTO_MY_CLIENT_NET}/$ENV{PLUTO_MY_CLIENT_MASK} -d $ENV{PLUTO_PEER_CLIENT_NET}/$ENV{PLUTO_PEER_CLIENT_MASK} -j ACCEPT";
		}else{
			#down stuff
			system("ipchains -D forward -b -s $ENV{PLUTO_MY_CLIENT_NET}/$ENV{PLUTO_MY_CLIENT_MASK} -d $ENV{PLUTO_PEER_CLIENT_NET}/$ENV{PLUTO_PEER_CLIENT_MASK} -j ACCEPT");
			$command = undef;
		}
	}else{
		#host stuff
	}

	Sauce::Util::replaceblock(
		"/etc/ipchains.conf",
		$start,
		$command,
		$end,
		);

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
