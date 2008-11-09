#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: am_arkeia.pl,v 1.3 2001/06/13 17:40:51 will Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
#
#
# Description:
# 	Syncronizes a boolean property in a namespace with the same property
# in the ActiveMonitor object.  The namespace and property names in ActiveMonitor
# must match that of the original object.
#
use strict;
use CCE;

my $cce = new CCE;
$cce->connectfd();

# retrieve information about the object being changed
my $oid = $cce->event_oid();
my $ns = $cce->event_namespace();
my $prop = $cce->event_property();
my ($ok, $newobj, $oldobj) = $cce->get($oid, $ns);

#
# Change the ActiveMonitor state only on a change of boolean value,
# not just a string value.
#
my $newval = $newobj->{$prop} ? 1 : 0;
my $oldval = $oldobj->{$prop} ? 1 : 0;

if ($newval != $oldval) {
	my @oids = $cce->find("ActiveMonitor");

	# Update the cooresponding ActiveMonitor namespace property
	my $rc = $cce->set($oids[0], $ns, {$prop => $newval});
	if ($rc != 0) {
		$cce->bye('FAIL');
		exit(1);
	}
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
