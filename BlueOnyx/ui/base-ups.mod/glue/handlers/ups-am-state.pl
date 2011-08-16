#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: ups-am-state.pl,v 1.4 2001/06/28 04:01:26 uzi Exp $
# Copyright 2001 Sun Microsystems, Inc., All rights reserved.
#
# Author: Joshua Uziel
# Enable and disable UPS Active Monitoring

use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();
my ($ups_ok, $upsoid) = $cce->get($cce->event_oid(), 'UPS');
if (not $ups_ok) {
	$cce->bye('FAIL');
	exit(1);
}

# Get the Active Monitor OID
@oids = $cce->find('ActiveMonitor');
my ($am_ok, $amoid) = $cce->get($oids[0], 'UPS');
if (not $am_ok) {
	$cce->bye('FAIL');
	exit(1);
}

if (($upsoid->{state} eq 'master') or ($upsoid->{state} eq 'slave')) {
	# Turn on the UPS AM monitoring.
	$cce->set($oids[0], 'UPS', { enabled => '1' }) if ($#oids >= 0);
} else {
	# Disabled, turn it off.
	$cce->set($oids[0], 'UPS', { enabled => '0' }) if ($#oids >= 0);
}

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
