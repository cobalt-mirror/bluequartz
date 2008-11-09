#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $DEBUG=0;

if($DEBUG){
	open D, "+>/tmp/editProfile.pl.$$";
	print D scalar localtime;
}

my $obj = $cce->event_object();

my $connName = $obj->{name};
$connName =~ s/\s/_/g;

my $pfs = $obj->{pfs}?"yes":"no";


if($obj->{enableAutoRsa} || $obj->{enableAutoSecret}){
$autoConn = << "EOF";
conn $connName-auto
        auth=$obj->{auth}
        pfs=$pfs
        keylife=$obj->{keylife}m
        rekeymargin=$obj->{rekeymargin}m
        rekeyfuzz=$obj->{rekeyfuzz}%
        keyingtries=$obj->{keyingtries}
        ikelifetime=$obj->{ikelifetime}m

EOF
}else{
	$autoConn="";
}

print D "autoConn:\n" if $DEBUG;
print D $autoConn if $DEBUG;

if($obj->{enableManual}){
$manualConn = << "EOF";
conn $connName-manual
        $obj->{manualAuth}=$obj->{authValue}
        $obj->{manualAuth}replay_window=$obj->{replayWindow}

EOF
}else{
	$manualConn="";
}

print D "manualConn:\n" if $DEBUG;
print D $manualConn if $DEBUG;

my $startString = "# BEGIN auto-generated profile $obj->{name}, DO NOT EDIT\n";
my $profileString = << "EOF";
$autoConn

$manualConn

EOF

print D "profileString:\n" if $DEBUG;
print D $profileString if $DEBUG;

my $endString = "# END auto-generated profile $obj->{name}, DO NOT EDIT\n";

$ret=Sauce::Util::replaceblock(
	"/etc/freeswan/ipsec.conf.profiles",
	$startString,
	$profileString,
	$endString,
	0600
);

print D "ret=$ret\n" if $DEBUG;

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
