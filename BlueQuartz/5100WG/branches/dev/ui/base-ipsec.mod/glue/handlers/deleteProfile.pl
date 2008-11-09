#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use CCE;
use Sauce::Util;
#use Data::Dumper;

my $cce = new CCE;
$cce->connectfd();
 
my $obj = $cce->event_old();

#open D, "+>/tmp/deleteProfile.pl.$$";
#print D Dumper $obj;
#close D;

my $startString = "# BEGIN auto-generated profile $obj->{name}, DO NOT EDIT\n";
my $endString = "# END auto-generated profile $obj->{name}, DO NOT EDIT\n";

my @oids = $cce->find("IpsecTunnel", {profile=>$obj->{name}});

if(scalar(@oids)){
	my @tunnelList;
	foreach(@oids){
		my $tunnel = ($cce->get($_))[1];
		next if $tunnel->{enabled} eq "ignore";
		push @tunnelList, $tunnel->{name};

#		$cce->set($_, {enabled=>"ignore", profile=>""});
	}

	if(scalar @tunnelList){
		my $list=join ",", @tunnelList;
		$cce->warn("[[base-ipsec.profileInUse,list=\"$list\"]]");
		$cce->bye("FAIL");
		exit(1);
	}
}

#remove the profile
Sauce::Util::replaceblock(
        "/etc/freeswan/ipsec.conf.profiles",
        $startString,
        undef,
        $endString,
        0600
); 

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
