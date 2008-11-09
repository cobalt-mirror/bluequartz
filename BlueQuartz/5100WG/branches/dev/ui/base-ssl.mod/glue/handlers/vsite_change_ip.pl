#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: vsite_change_ip.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
#
# make sure if a vsite's ip address is changed that two sites with the
# same IP address don't end up having SSL enabled
#

use CCE;

my $cce = new CCE('Domain' => 'base-ssl', 'Namespace' => 'SSL');
$cce->connectfd();

my $ssl_info = $cce->event_object();

# if SSL is not enabled, just exit with success
if (!$ssl_info->{enabled}) {
	$cce->bye('SUCCESS');
	exit(0);
}

# need to get the vsite for the IP address
my ($ok, $vsite) = $cce->get($cce->event_oid());
if (!$ok) {
	$cce->bye('FAIL', 'cantGetVsite');
	exit(1);
}

my @other_sites = $cce->find('Vsite',
			     {
				'ipaddr' => $vsite->{ipaddr},
				'SSL.enabled' => 1
			     });

if (scalar(@other_sites) > 1) {
	$cce->bye('FAIL', 'sslMigrationConflict',
		  { 'ipaddr' => $vsite->{ipaddr} });
	exit(1);
}

# otherwise, everything is okay
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
