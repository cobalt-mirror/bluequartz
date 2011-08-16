#!/usr/bin/perl -w
# delete all imported users, groups, and mailLists

use lib qw(/usr/sausalito/perl);
#use strict;
use CCE;

my $cce = new CCE;
$cce->connectuds();

use Getopt::Std;

my $opts = {};
use vars qw( $opts );
getopts('gumv', $opts);
if(!$opts->{u} && !$opts->{g} && !$opts{m} && !$opts{v}) {
	delVsites();
	delUsers();
	#delGroups();
	delMailLists();
}

if($opts->{u}) { delUsers() } 
if($opts->{g}) { delGroups() } 
if($opts->{m}) { delMailLists() } 
if($opts->{v}) { delVsites() } 



sub delUsers {
my @oid = $cce->find("User");

foreach my $curOid (@oid) {
    my ($ok, $object, $oid, $new) = $cce->get($curOid);
    if ($object->{name} ne 'admin') {
	print "destroying user $object->{name}\n";
	$cce->destroy($curOid);
    }
}
}

sub delVsites {
my @oid = $cce->find("Vsite");

foreach my $curOid (@oid) {
    my ($ok, $object, $oid, $new) = $cce->get($curOid);
	print "destroying user $object->{name}\n";
	$cce->destroy($curOid);
    }
}


sub delGroups {

my @oid = $cce->find("Workgroup");
foreach my $curOid (@oid) {
    my ($ok, $object, $oid, $new) = $cce->get($curOid);
    if ($object->{name} ne 'home' && 
	$object->{name} ne 'restore' &&
	$object->{name} ne 'guest-share') {
	print "destroying group $object->{name}\n";
	$cce->destroy($curOid);
    }
}
}

sub delMailLists {
my @oid = $cce->find("MailList");
foreach my $curOid (@oid) {
    my ($ok, $object, $oid, $new) = $cce->get($curOid);
    if ($object->{group} eq '') {
	print "destroying mailList $object->{name}\n"; 
	$cce->destroy($curOid);
    }
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
