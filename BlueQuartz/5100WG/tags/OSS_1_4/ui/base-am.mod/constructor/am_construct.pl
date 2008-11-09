#!/usr/bin/perl -w -I/usr/sausalito/perl -I.

use strict;
use CCE;

my ($cce, @oids);
my $oid = 0;
my $success = 0;

$cce = new CCE;
$cce->connectuds();

# check if AM exists already
@oids = $cce->find("ActiveMonitor");
if ($#oids >= 0) {
	# check if it is exactly 1 
	if ($#oids > 0) {
		#FIXME: cleanup in the case of a screwup
		print STDERR "We already have " . length(@oids) 
			. " ActiveMonitor object(s)\n";
	}
	$success = 1;
	$oid = $oids[0];
} else {
	# add
	$success = $cce->create("ActiveMonitor", 
		{ alertEmailList => "admin",
		  enabled => "true"});

	if ($success) {
		@oids = $cce->find("ActiveMonitor");
		if ($#oids >= 0) {
			$oid = $oids[0];
			$success = 1;
		} else {
			$success = 0;
		}
	}
}

# failed?
if(!$success) {
  print STDERR "Constructor for ActiveMonitor failed\n";
  $cce->bye();
  exit 1;
}

# clear old data
my @names;
($success, @names) = $cce->names($oid);
foreach $_ (@names) {
	$cce->set($oid, $_, {
		currentState => "N",
		currentMessage => "",
		lastChange => time()});
}

$cce->bye();

exit 0;
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
