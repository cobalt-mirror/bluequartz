#!/usr/bin/perl -w
# $Id: cceToXml.pl 922 2003-07-17 15:22:40Z will $

# A Qube 3 scanout without an Xml parser

use strict;
use CCE;

my %usrTrans = (
		name                  => 'userName value',
		description           => 'description value',
		crypt_password        => 'passwd value',
		fullName              => 'fullName value',
		systemAdministrator   => 'siteAdmin bool',
		#enabled              => 'usrEnabled bool',
		localePreference      => 'localPrefernce value',
		stylePreference       => 'stylePreference value',
		apop                  => 'apop bool',
		vacationOn            => 'vacation bool',
		vacationMsg           => 'vacationMsg value',
		aliases               => 'aliases',
		forwardSave           => 'forwardSave bool',
		forwardEnable         => 'forwardEnable bool',
		forwardEmail          => 'forward value');

my %grpTrans = (
		name                  => 'groupName value',
		decription            => 'grpdescription value',
		members               => 'grpMembers',
		quota                 => 'grpQuota value');
		
my %mlTrans = (
	       name                   => 'listName value',
	       apassword              => 'listPasswd value',
	       description            => 'listDescription value',
	       maxlength              => 'maxlength int', 
	       replyToList            => 'replyToList bool',
	       postPolicy             => 'postPolicy value',
	       subPolicy              => 'subPolicy value',
	       local_recips           => 'lrecips',
	       remote_recips          => 'xrecips',
	       moderator              => 'moderator value');

my %subTrans = (
		local_recips          => 'lrecips value',
		remote_recips         => 'xrecips value',
		aliases               => 'alias value',
		members               => 'member value');
		

my $cce = new CCE;
$cce->connectuds();

print "<migrate>\n";

# first we get users

my @oid = $cce->find("User");

foreach my $curOid (@oid) {
    print "  <user>\n";

    my ($ok, $object) = $cce->get($curOid);
    
    foreach my $attr (keys %$object) {
	print "     <$usrTrans{$attr} = \"$object->{$attr}\"/>\n" if exists $usrTrans{$attr};
    }

    ($ok, $object) = $cce->get($curOid, 'Email');

    foreach my $attr (keys %$object) {
	if (exists $usrTrans{$attr}) {
	    my $xmlVal = $object->{$attr};
	    if ($xmlVal =~ /^&/) { # array type item
                print "     <$usrTrans{$attr}>\n";
                my @arrayVals = split /&/, $xmlVal;
                foreach my $curVal (@arrayVals) {
                    print "        <$subTrans{$attr} = \"$curVal\"/>\n" if $curVal ne "";
		}
		print "     </$usrTrans{$attr}>\n";
	    }
	    else {
		print "     <$usrTrans{$attr} = \"$object->{$attr}\"/>\n";
	    }
	}
    }

    print "  </user>\n";
}

@oid = $cce->find("Workgroup");
foreach my $curOid (@oid) {
    print "  <group>\n";
    my ($ok, $object) = $cce->get($curOid);
 
    foreach my $attr (keys %$object) {
	if (exists $grpTrans{$attr}) {
            my $xmlVal = $object->{$attr};
            if ($xmlVal =~ /^&/) { # array type item
		print "     <$grpTrans{$attr}>\n";
		my @arrayVals = split /&/, $xmlVal;
		foreach my $curVal (@arrayVals) {
		    print "        <$subTrans{$attr} = \"$curVal\"/>\n" if $curVal ne "";
		}
		print "     </$grpTrans{$attr}>\n";
	    }
	
	else {
	    print "     <$grpTrans{$attr} = \"$object->{$attr}\"/>\n";
	}
	
	}
    }


    ($ok, $object) = $cce->get($curOid, 'Disk');
 
    foreach my $attr (keys %$object) {
	print "     <$grpTrans{$attr} = \"$object->{$attr}\"/>\n" if exists $grpTrans{$attr};
    }
    print "  </group>\n"

    }

@oid = $cce->find("MailList");
foreach my $curOid (@oid) {
    print "  <mailingList>\n";
    my ($ok, $object) = $cce->get($curOid);

    foreach my $attr (keys %$object) {
	if (exists $mlTrans{$attr}) {
	    my $xmlVal = $object->{$attr};
	    if ($xmlVal =~ /^&/) { # array type item
		print "     <$mlTrans{$attr}>\n";
		my @arrayVals = split /&/, $xmlVal;
		foreach my $curVal (@arrayVals) {
		    print"        <$subTrans{$attr} = \"$curVal\"/>\n" if $curVal ne "";
		}
		print "     </$mlTrans{$attr}>\n";
	    }
	    else {
		print "     <$mlTrans{$attr} = \"$object->{$attr}\"/>\n"
	    }
	}
    }
    print "  </mailList>\n";
}

print "</migrate>\n";

$cce->bye("CMU");






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
