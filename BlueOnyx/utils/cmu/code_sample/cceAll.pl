#!/usr/bin/perl -w -I/usr/sausalito/perl

use strict;
use CCE;
use MIME::Base64;

my %enc_fields = ( fullName  => 1,
		   sortName  => 1,
		   description => 1,
		   vacationMsg => 1);

my $cce = new CCE;
$cce->connectuds();

my @oid = $cce->find("User");
print "-Users-\n";
foreach my $curOid (@oid) {
    my ($ok, $object, $old, $new) = $cce->get($curOid);
    my %obj = %$object;
    foreach my $attr (keys %obj) {
	$obj{$attr} = encode_base64($obj{$attr}, "") if $enc_fields{$attr};
	print "  $attr = \"$obj{$attr}\"\n";
	
    }

    print "  User.Email---\n";

    ($ok, $object, $old, $new) = $cce->get($curOid, 'Email');
    %obj = %$object;
    foreach my $attr (keys %obj) {
	$obj{$attr} = encode_base64($obj{$attr}, "") if $enc_fields{$attr};
	print "  $attr = \"$obj{$attr}\"\n";
    }

    print "  User.Disk---\n";

    ($ok, $object, $old, $new) = $cce->get($curOid, 'Disk');
    %obj = %$object;
    foreach my $attr (keys %obj) {
	print "  $attr = \"$obj{$attr}\"\n";
    }

    print "\n";
}



@oid = $cce->find("Workgroup");
print "-Groups-\n";
foreach my $curOid (@oid) {
    my ($ok, $object, $old, $new) = $cce->get($curOid);
    my %obj = %$object;
    foreach my $attr (keys %obj) {
	$obj{$attr} = encode_base64($obj{$attr}, "") if $enc_fields{$attr};
	print "  $attr = \"$obj{$attr}\"\n";
    }

    print "  Group.Disk---\n";

    ($ok, $object, $old, $new) = $cce->get($curOid, 'Disk');
    %obj = %$object;
    foreach my $attr (keys %obj) {
	print "  $attr = \"$obj{$attr}\"\n";
    }

    print "\n";
}

@oid = $cce->find("System");
print "-System-\n";
foreach my $curOid (@oid) {
    my ($ok, $object, $old, $new) = $cce->get($curOid);
    my %obj = %$object;
    foreach my $attr (keys %obj) {
	print "  $attr = \"$obj{$attr}\"\n";
    }
    print "\n";
}

@oid = $cce->find("Network");
print "-Network-\n";
foreach my $curOid (@oid) {
    my ($ok, $object, $old, $new) = $cce->get($curOid);
    my %obj = %$object;
    foreach my $attr (keys %obj) {
	print "  $attr = \"$obj{$attr}\"\n";
    }
    print "\n";
}

@oid = $cce->find("MailList");
print "-MailLists-\n";
foreach my $curOid (@oid) {
    my ($ok, $object, $old, $new) = $cce->get($curOid);
    my %obj = %$object;
    foreach my $attr (keys %obj) {
	print "  $attr = \"$obj{$attr}\"\n";
    }
    print "\n";
}


@oid = $cce->find("System");
print "-System Email-\n";
foreach my $curOid (@oid) {
    my ($ok, $object, $old, $new) = $cce->get($curOid, 'Email');
    my %obj = %$object;
    foreach my $attr (keys %obj) {
	print "  $attr = \"$obj{$attr}\"\n";
    }
    print "\n";
}


@oid = $cce->find("System");
print "-System Ftp-\n";
foreach my $curOid (@oid) {
    my ($ok, $object, $old, $new) = $cce->get($curOid, 'Ftp');
    my %obj = %$object;
    foreach my $attr (keys %obj) {
	print "  $attr = \"$obj{$attr}\"\n";
    }
    print "\n";
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
