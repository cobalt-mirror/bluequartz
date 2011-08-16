#!/usr/bin/perl
# $Id: delete.pl,v 1.7 2001/10/24 18:12:57 will Exp $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# Description:
# 	Deletes a schedule permanently by removing it's file from /etc/cron.d
#
use CCE;
use Schedule;

$DEBUG = 0;

my $cce = new CCE;
$cce->connectfd();

# Get information on the object being deleted.
my $oid = $cce->event_oid();
my $obj = $cce->event_old();

$DEBUG && print STDERR 'Schedule delete: received object ', Dumper($obj);

# Remove the schedule from the timer
my $ok = Schedule::timer_delete($cce, $obj);
if (! $ok) {
	$DEBUG && print STDERR 'Schedule delete: timer_delete returned: ' .
	    $ok . "\n";
	$cce->bye('FAIL', '[[base-schedule.cannot_delete_timer]]');
	exit(1);
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
