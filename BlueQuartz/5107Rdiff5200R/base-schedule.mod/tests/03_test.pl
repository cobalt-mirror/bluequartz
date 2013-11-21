#!/usr/bin/perl -I /usr/sausalito/perl

# $Id: 03_test.pl,v 1.3 2001/09/28 22:26:22 bservies Exp $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#

#
# Description:
#	Create Schedule items and perform boundary checks on parameter values.
#
use Cwd;
use File::Basename;
use CCE;

sub failed {
	my ($cce, $oid, $message) = @_;

	# Attempt to delete the object just created
	if ($oid) {
		my ($ok, @info) = $cce->destroy($oid);
		if (! $ok) {
			warn "$testname failed: could not destroy Schedule object $oid."
		}
	}
	$cce->bye();

	die $message;
}


# Determine the base name of this test
my ($testname, $path, $suffix) = fileparse($0, '');
my $dir = cwd();
$action = $dir . "/test_action --name=$testname";
my $class = "Schedule";

%defaults = (
	'name'		=> $testname,
	'enabled'	=> 0,
	'start_time'	=> time(),
	'interval'	=> 'minute',
	'action'	=> $action,
);

#
# All test hashs have the form:
#	test case => [ value, expected result, ... ],
#
%boolean_tests = (
	'empty'		=> [ '', 0, ],
	'too_big'	=> [ 30, 0 ],
	'too_small'	=> [ -1, 0 ],
	'true'		=> [ 1, 1 ],
	'false'		=> [ 0, 1 ],
);

%string_tests = (
	'empty'		=> [ '', 1 ],
	'default'	=> [ $testname, 1, ],
	'path'		=> [ '/bin/echo', 1, ],
	'alpha-numeric'	=> [ 'abc123ABC456', 1, ],
	'alpha'		=> [
	    'abcdefghijklmnopqrstuvxyzABCDEFGHIJKLMNOPQRSTUVXYZ', 1,
	],
	'numeric'	=> [ '0123456789', 1 ] ,
	'punctuation'	=> [ '!@#$%^&*()_+=-[]\\{}|,./?><;:\'"', 1 ],
);

%utc_tests = (
	'empty' 	=> [ '', 0, ],
	'valid'		=> [ time(), 1, ],
	'invalid'	=> [ -1, 0, ],
	'string'	=> [ scalar localtime(time()), 0, ],
);

%interval_tests = (
	'empty'		=> [ '', 0, ],
	'minute'	=> [ 'minute', 1, 'Minute', 1, 'miNuTe', 1, ],
	'quarter-hourly' => [
	    'quarter-hourly', 1, 'Quarter-Hourly', 1, 'quarteR-houRly', 1,
	],
	'hourly'	=> [ 'hourly', 1, 'Hourly', 1, 'hoURLy', 1, ],
	'quarter-daily'	=> [
	    'quarter-daily', 1, 'Quarter-Daily', 1, 'qUARter-dAIly', 1,
	],
	'daily'		=> [ 'daily', 1, 'Daily', 1, 'dAILy', 1, ],
	'weekly'	=> [ 'weekly', 1, 'Weekly', 1, 'wEEKly', 1, ],
	'monthly'	=> [ 'monthly', 1, 'Monthly', 1, 'monTHly', 1, ],
	'invalid'	=> [ 'invalid', 0, 'Invalid', 0, 'inVALid', 0, ],
);

%action_tests = (
	'empty'		=> [ '', 0, ],
	'valid'		=> [ $action, 1, '/bin/echo', 1, ],
	'invalid'	=> [ '/etc/motd', 0, '/invalid/filename', 0, ],
);

#
# The tests to perform.  Each attribute has a hash of tests cases.
#
%tests = (
	'name'		=> \%string_tests,
	'enabled'	=> \%boolean_tests,
	'start_time'	=> \%utc_tests,
	'interval'	=> \%interval_tests,
	'action'	=> \%action_tests,
);

# Create a connection to CCE
my $cce = new CCE;
$cce->connectuds();


#
# Creation Tests
#
# Create a new object for every test.
#
$passed = 0;
$total_tests = 0;
foreach $property (sort keys %tests) {
	my %cases = %{$tests{$property}};
	foreach $case (keys %cases) {
		my @values = @{$cases{$case}};
		while (@values) {
			my $value = shift @values;
			my $result = shift @values;

			#
			# Create the property list for the new object using
			# the defaults along with the value being tested.
			#
			my %object = %defaults;
			$object{$property} = $value;

			# About to run a test.  Increment the test counter.
			$total_tests++;

			# Try to create the object in CCE
			my ($ok, $badkeys, @info) =
			    $cce->create($class, \%object);
			if ($ok != $result) {
				failed($cce, 0,
				    "$testname failed: creating \"$class\" with property \"$property\" set to value \"$value\" returned \"$ok\" instead of \"$result\".\n");
			} else {
				$passed++;
			}

			if ($ok) {
				#
				# Destroy the object we just created so CCE
				# does not get cluttered.
				#
				my $oid = $cce->oid();
				$cce->destroy($oid);
			}
		}
	}
}

warn "$passed of $total_tests tests passed.\n";
$cce->bye();

0;

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
