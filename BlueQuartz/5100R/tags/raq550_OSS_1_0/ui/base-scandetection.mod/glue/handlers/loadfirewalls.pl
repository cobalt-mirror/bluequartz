#!/usr/bin/perl -I/usr/sausalito/perl -I.
#
# Name: loadfirewalls.pl
# Author: Jesse Throwe
# Description: This script reloads the firewalls when changes are made.
# Copyright 2001 Sun Microsystems, Inc. All rights reserved.
# $Id: loadfirewalls.pl,v 1.3 2001/11/01 23:22:31 ge Exp $

# set up changeable variables
$ldfirewall = "/usr/sbin/ldfirewall";
$firewallfile = "/etc/scandetection/scandetection.fwall";

# initialize CCE
use CCE;

my $cce = new CCE;
$cce->connectuds();


# find our system OID
my @system_oid = $cce->find("System");


# get the Scandetection object
my ($ok, $object, $old, $new) = $cce->get($system_oid[0], 'Scandetection');

# check to make sure CCE/Scandetection object is ok, leave if not
if (!$ok) {
	exit(0);
}

# acquire all of our values for our objects

my ($paranoiaLevel) = $cce->scalar_to_array($object->{paranoiaLevel});
my ($timeout) = $cce->scalar_to_array($object->{timeout});
my ($numScans) = $cce->scalar_to_array($object->{numScans});
my ($alertEmail) = $cce->scalar_to_array($object->{alertEmail});
my @permBlocked = $cce->scalar_to_array($object->{permBlocked});
my @permUnblocked = $cce->scalar_to_array($object->{permUnblocked});
	
my @networks = $cce->find("Network");


# find out if we are even doing anything
if ($paranoiaLevel == 0) { 
	exit 0; 
}

# now we find our interfaces to put firewalls on, and install them

foreach $networkoid (@networks) {
	 my ($ok, $networkobject) = $cce->get($networkoid);
	 my ($interface) = $cce->scalar_to_array($networkobject->{device});
	 my ($enabled) = $cce->scalar_to_array($networkobject->{enabled});
	 my ($real) = $cce->scalar_to_array($networkobject->{real});
	 if ($enabled && $real) {
	     system("$ldfirewall $interface $firewallfile > /dev/null");
         }
 }


# now to cleanup
$cce->bye('SUCCESS');
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
