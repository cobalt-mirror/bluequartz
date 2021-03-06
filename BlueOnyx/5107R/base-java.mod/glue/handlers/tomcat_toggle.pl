#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: tomcat_toggle.pl,v 1.8.2.1 2002/03/05 01:07:27 pbaltz Exp $
# Copyright 2000-2002 Sun Microsystems, Inc., All rights reserved.
# Tomcat Virtual Site Monitor
#
# Test whether any virtual sites are using Java.
# en/disable asp-apache-3000 if necessary
#
# we determine whether Java is "enabled" if any virtual site is subscribed.

my $DEBUG = 0;
$DEBUG && warn `date`."$0\n";

use CCE;
my $cce = new CCE;
$cce->connectfd();

my $object = $cce->event_object();

# Test proposed Vsite.Java state

my $oid;
my $enabled = 0;
my ($ok, $siteJsp) = $cce->get($cce->event_oid(), 'Java');

$enabled = 1 if ($siteJsp->{enabled});

# search namespace, so there is no need to get all Java namespaces
unless($enabled)
{
	my @voids = $cce->find('Vsite', { 'Java.enabled' => 1 });
	$DEBUG && warn "vsites with java: ".join(':',@voids)."\n";
	if (scalar(@voids))
	{
		$enabled = 1;
	}
}

my ($sysoid) = $cce->find("System");
$DEBUG && warn "Enable JSP? $enabled; system OID: $sysoid\n";

my ($status, $obj) = $cce->get($sysoid, "Java");

# fix chkconfig information:
if ($obj->{enabled} && !$enabled) 
{
	$DEBUG && warn "Calling CCE to disable System.Java enabled...\n";
	$ok = $cce->set($sysoid, 'Java', {'enabled' => 0});
} 
elsif (!$obj->{enabled} && $enabled) 
{
	$DEBUG && warn "Calling CCE to enable System.Java enabled...\n";
	$ok = $cce->set($sysoid, 'Java', {'enabled' => 1});
}

# do not block w/failures
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
