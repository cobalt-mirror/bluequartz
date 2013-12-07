# $Id: Schedule.pm
#
# Copyright 2001, Sun Microsystems, Inc.  All rights reserved.
#

package Schedule;

use Time::Local;
use Sauce::Util;

#
#	Attribute  Check
#	---------  -----------------------------------------------------------
# 	name       None.  This property may have any scalar value.
#	enabled    None.  CCE checks this boolean property for correctness.
#	start_time Make sure the UTC time value can be converted to the local
#		   time string and back.
#	interval   None.  CCE checks this interval_type for correctness.
#	action     Make sure the first word in this scalar is a path to a
#		   valid file that is executable.  Also ensure the path is
#		   absolute.
#
sub check_parameters {
	my ($cce, $obj) = @_;
	my $oid = $cce->event_oid();
	my $start_time = $obj->{'start_time'};
	my $new_time = Time::Local::timelocal(localtime($start_time));
	if ($start_time != $new_time) {
		$DEBUG && warn 'start_time check failed: ' . $start_time .
		    ' != ' . $new_time . "\n"; 
		$cce->baddata($oid, 'start_time',
		    '[[base-schedule.bad_start_time]]');
		return(0);
	}

	my ($action) = split /\s+/, $obj->{'action'};
	if (! -x $action || $action !~ /^\//) {
		#
		# The action does not exist, is not executable, or does not
		# contain an absolute path.
		#
		$DEBUG && warn 'action does not exist: ' . $action . "\n";
		$cce->baddata($oid, 'action', '[[base-schedule.bad_action]]');
		return(0);
	}

	# Success
	return(1);
}

#
# Description:
# 	Add a schedule to cron.
#
sub timer_add {
	my ($cce, $obj) = @_;
	my $ok = 1; # failure

	# Convert the start time into it's constituent components
	my ($second,
	    $minute,
	    $hour,
	    $dayofmonth,
	    $month,
	    $year,
	    $dayofweek,
	    $dayofyear,
	    $isdst) = localtime($obj->{'start_time'});

	# Adjust localtime values to ranges required for the Timer.
	$month++;

	#	
	# Convert the start time to cron time based on the repeat interval
	# See the requirements document for a table summary of this conversion.
	#
	$interval = $obj->{'interval'};
	if ($interval =~ /none/i) {
		# Do nothing to add the schedule to the cron system.
		return(1);

	} elsif ($interval =~ /minute/i) {
		$minute = '*';
		$hour = '*';
		$dayofmonth = '*';
		$month = '*';
		$dayofweek = '*';

	} elsif ($interval =~ /quarter-hourly/i) {
		foreach $a (0, 15, 30, 45) {
			$b = $minute + $a;
			$c = $b > 59 ? $b - 60 : $b;
			push @minutes, ($c);
		}
		$minute = join ',', sort {$a <=> $b} @minutes;
		$hour = '*';
		$dayofmonth = '*';
		$month = '*';
		$dayofweek = '*';

	} elsif ($interval =~ /hourly/i) {
		$hour = '*';
		$dayofmonth = '*';
		$month = '*';
		$dayofweek = '*';

	} elsif ($interval =~ /quarter-daily/i) {
		foreach $a (0, 6, 12, 18) {
			$b = $hour + $a;
			$c = $b > 23 ? $b - 24 : $b;
			push @hours, ($c);
		}
		$hour = join ',', sort {$a <=> $b} @hours;
		$dayofmonth = '*';
		$month = '*';
		$dayofweek = '*';

	} elsif ($interval =~ /daily/i) {
		$dayofmonth = '*';
		$month = '*';
		$dayofweek = '*';

	} elsif ($interval =~ /weekly/i) {
		$dayofmonth = '*';
		$month = '*';

	} elsif ($interval =~ /monthly/i) {
		$month = '*';
		$dayofweek = '*';

	}

	$ok = Schedule::timer_createfile($cce, $obj);
	if (! $ok) {
		# Could not add the schedule to the timer
		return ($ok);
	}
	Sauce::Util::addrollbackcommand('rm ' . $obj->{'filename'});
	$DEBUG && warn 'timer filename: ' . $obj->{filename} . "\n";

	#
	# Open the file for writing and place the new scheduled action
	# into it using crontab(5) format.
	#
	$action = $obj->{'action'};
	$filename = $obj->{'filename'};
	$schedule = sprintf('%s %s %s %s %s', $minute, $hour, $dayofmonth,
	    $month, $dayofweek);
	$username = 'root';

	# Edit the timer (cron) file
	$ok = Sauce::Util::editfile($filename, *Schedule::timer_edit,
	    $schedule, $username, $action);
	if ($ok) {
		# The edit was successful.
		# Make sure the mode of the file is correct.
		Sauce::Util::chmodfile(0600, $filename);

		# remove the backup copy or cron will run them both...
		unlink($filename . '~');

		# Success 
		$ok = 1;
	} else {
		# Failure
		$ok = 0;
	}

	return ($ok);
}


sub timer_createfile
{
	my ($cce, $obj) = @_;
	my $oid;
	my $namespace;
	my $result = 1; # Success

	# Set the umask so that the file is only executable by the owner.
	umask(077);

	#
	# Note: I do not use the POSIX::tmpnam() function because it will
	# not accept a prototype and therefore cannot create unique files
	# in /etc/cron.d.
	#
	open FNAME, '/bin/mktemp /etc/cron.d/schedule_XXXXXX |';
	my @lines = <FNAME>;
	close FNAME;
	if ($? != 0) {
		# mktemp failed.
		$result = 0;

		# make sure the file doesn't actually exist
		foreach $filename (@lines) {
			chomp $filename;
			if (-f $filename) {
				unlink $filename;
			}
		}
	} else {
		# mktemp succeeded.  Get the filename and update the object
		my $filename = $lines[0];
		chomp $filename;
		$obj->{'filename'} = $filename;

		# Determine the objects OID and namespace
		$oid = $cce->event_oid();
		$namespace = $cce->event_namespace();

		# Set the filename in CCE
		($result) = $cce->set($oid, $namespace,
		    {'filename' => $filename});
	}
	return ($result);
}


sub timer_delete {
	my ($cce, $obj) = @_;
	my $oid;
	my $namespace;
	my $ok = 1;

	#
	# A new unique schedule file is created every time the action is
	# added or modified.  So, we can just unlink the file here.
	#
	if (-e $obj->{'filename'}) {
		#
		# Remove the schedule from the timer by deleting the associated cron
		# file.
		#
		$DEBUG && warn 'removing timer file: ' . $obj->{'filename'} .
		    "\n";
		$count = unlink($obj->{'filename'});
		if ($count != 1) {
			# unlink failed
			$ok = 0;
			$DEBUG && warn 'unlink timer file failed: ' . $ok .
			    "\n";
		}

		# Determine the objects OID and namespace
		$oid = $cce->event_oid();
		$namespace = $cce->event_namespace();

		#
		# Remove the filename from the object and reset the result
		# value, but only if it is not already failure.
		#
		if ($ok) {
			#
			# Ignore the result here.  If the object is removed
			# from the timer as part of a modify, then this will
			# succeed.  If it is destroyed as part of a schedule
			# descruction, it will fail, and that's OK.
			#
			$cce->set($oid, $namespace, {'filename' => ''});
		}
	}
	return ($ok);
}


sub timer_edit {
	my ($ifile, $ofile, $schedule, $username, $action) = @_;

	#
	# Just write the new schedule to the output file, replacing all
	# contents from the previous version.
	#
	print $ofile "$schedule\t$username\t$action\n";

	# This routine must return 1 for success or editfile will fail.
	return (1);
}

1;

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
