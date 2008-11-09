#!/usr/bin/perl -w -I/usr/sausalito/perl/

use strict;
use CCE;

my $cce=new CCE;
$cce->connectfd();
my $obj=$cce->event_object();

my $dhclientLeased = "/var/state/dhcp/LEASED";
my $configFile = "/etc/dhclient.conf";
my $count;



if($obj->{bootproto} ne "dhcp" || not $obj->{enabled}){

	killDhclient();

        $cce->bye("SUCCESS");
}else{
	#interface dhcp'd

	killDhclient();

       system("rm -f $dhclientLeased-$obj->{device}");
       if(!fork()){
	       #child
	       exec("/sbin/dhclient -cf ".$configFile.".$obj->{device} $obj->{device}");
		#see dhclient.pl for the reasoning behind per-interface config files.
	       exit 0;
       }


	while(!-e "$dhclientLeased-$obj->{device}"){
	       sleep(1);
               last if ++$count >=60;
        }
 
        if(-e "$dhclientLeased-$obj->{device}"){
                $cce->bye("SUCCESS");
        }else{
		killDhclient();
        	$cce->warn("[[base-dhclient.".$obj->{device}."Timeout]]");
		$cce->bye("FAIL");
         }
 
}                                                                               

sub killDhclient{

#darn screwey ps -fC cuts off process listings at 80 chars.
# good point but lets do this instead since we only need
# pid and cmd name, otherwise if there is some random command
# that has eth0 or eth1 in it (I know slim chance, but possible.)
# we don't end up killing it

	my $ps = `/bin/ps --no-headers -o "pid cmd" -C dhclient`;

        if($ps){
       	        my @dhclient = split/\n/,$ps;
                	if(($_)=grep {/$obj->{device}/} @dhclient){
                	s/^\s+//;
        	        my $pid=(split/\s+/)[0];
       	        	unless(kill 15,$pid){ #try being polite first (SIGTERM)
		                kill 9,$pid; #die, damnit. (SIGKILL)
			}
       	        }
	}
}

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
