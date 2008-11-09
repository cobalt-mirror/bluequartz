#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $DEBUG=0;

if($DEBUG){
	open D,"+>/tmp/deleteTunnel.pl.$$";
	print D scalar localtime;
}

my $old = $cce->event_old();

my $startString = "# BEGIN auto-generated tunnel $old->{name}.  DO NOT EDIT";
my $endString = "# END auto-generated tunnel $old->{name}.  DO NOT EDIT";

print D "$startString\n$endString\n" if $DEBUG;

my $tname = $old->{name};
$tname =~ s/\s/_/g;

print D $tname if $DEBUG;

if($old->{keying} eq "maunal"){
	print D "keying = manual\n" if $DEBUG;
	my $t;
	$t=`ipsec manual --down $tname-$_` foreach(qw/nn hh hn nh/);
	$t.=`ipsec manual --unroute $tname-$_` foreach(qw/nn hh hn nh/);
	print D $t if $DEBUG;
}else{
	print D "keying != manual\n" if $DEBUG;
	my $t=`ipsec auto --delete $tname-$_` foreach(qw/nn hh hn nh/);
	print D $t if $DEBUG;
}

$ret=Sauce::Util::replaceblock(
        "/etc/freeswan/ipsec.conf.tunnels",
        $startString,
        undef,
        $endString,
        0600  
);

if($DEBUG){
	print D "\n$ret\n";
	close D;
}

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
