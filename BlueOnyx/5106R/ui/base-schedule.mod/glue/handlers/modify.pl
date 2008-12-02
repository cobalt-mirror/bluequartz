#!/usr/bin/perl
# $Id: modify.pl,v 1.7 2001/10/24 18:12:57 will Exp $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# Description:
#	Modifies a schedule by first removing it and then re-adding it to the
# timer.  A bit heavy handed, perhaps, but does the trick.
#
use CCE;
use Data::Dumper;
use Schedule;

use vars qw($DEBUG);
$DEBUG = 0;

my $cce = new CCE;
$cce->connectfd();

# Get information on the object being modified.
my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $result = 0;

$DEBUG && print STDERR 'Schedule modify: working on object: ', Dumper($obj);

# Make sure we are not trying to modify the filename parameter
my $changed = $cce->event_new();
if (defined $changed{'filename'}) {
	# 
	# The user is not permitted to change the filename property.  Fail
	# with bad data.
	# 
	$DEBUG && print STDERR 'Schedule modify: changed fields: ', Dumper($changed);
	$cce->baddata($oid, 'filename', '[[base-schedule.filename_private]]');
	$cce->bye('FAIL');
	exit(1);
}

# Check the modified parameters for the new object
$ok = Schedule::check_parameters($cce, $obj);
if (! $ok) {
	#
	# Bad parameters is set by the check_parameters routine.  Just fail
	# here.
	#
	$DEBUG && print STDERR 'Schedule modify: check_parameters returned: ' .
	    $ok . "\n";
	$cce->bye('FAIL');
	exit(1);
}


# Remove any old schedule from the timer (cron).
$ok = Schedule::timer_delete($cce, $obj);
if (! $ok) {
	# Failed to remove the previous version of this schedule
	$DEBUG && print STDERR 'Schedule modify: timer_delete returned: ' .
	    $ok . "\n";
	$cce->bye('FAIL', '[[base-schedule.timer_delete_failed]]');
	exit(1);
}


# Determine if the schedule is active and if it should be added to the timer.
if ($obj->{'enabled'}) {
	$ok = Schedule::timer_add($cce, $obj);
	if (! $ok) {
		# Failed to remove the previous version of this schedule
		$cce->bye('FAIL', '[[base-schedule.timer_add_failed]]');
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
