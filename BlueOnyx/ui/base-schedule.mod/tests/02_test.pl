#!/usr/bin/perl -I /usr/sausalito/perl

# $Id: 02_test.pl,v 1.2 2001/08/10 01:13:44 bservies Exp $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#

#
# Description:
#	This test attempts to create a basic scheduled action , allow it
# to execute once, and then turn it off.
#	Step 1:  Create a scheduled action
#	Step 2:  Wait for the scheduled action to occur
#	Step 3:  Disable the scheduled action
#	Step 4:  Wait to ensure the action is disabled
#
use Cwd;
use File::Basename;
use CCE;

sub failed {
	my ($cce, $oid, $message) = @_;

	# Attempt to delete the object just created
	my ($ok, @info) = $cce->destroy($oid);
	if (! $ok) {
		warn $testname . ' failed: could not destroy Schedule object' .
		   " $oid.\n";
	}
	$cce->bye();

	die $message;
}

# Create a connection to CCE
my $cce = new CCE;
$cce->connectuds();

# Determine the base name of this test
my ($testname, $path, $suffix) = fileparse($0, '');
my $dir = cwd();
$action = $dir . "/test_action --name=$testname";

# The properties to use when creating the object
my $class = 'Schedule';
my %properties = (
	'name' => $testname,
	'enabled' => 1,
	'start_time' => time(),
	'interval' => 'minute',
	'action' => $action,
);


#
# Step 1: Create a scheduled action
#
my ($ok, $badkeys, @info) = $cce->create($class, \%properties);
if (! $ok) {
	$cce->bye();
	die "$testname failed: could not create Schedule object."
}

# Get the OID of the object created.
my $oid = $cce->oid();

#
# Step 2: Wait for the action to run
#
# The test_action creates a file with the same name as the object in /tmp.
# Each line in that file contains the time at which the action was executed.
# We now wait for that file to be created and have at least 1 line of text.
#
my $filename = "/tmp/$testname";
my $retries = 20;
unlink $filename;
while ($retries && ! -f $filename) {
	sleep(5);
	$retries--;
}

if ($retries != 0 && ! -f $filename) {
	failed($cce, $oid, "$testname: scheduled action did not execute!");
}

my ($dev,
    $ino,
    $mode,
    $nlink,
    $uid,
    $gid,
    $rdev,
    $size,
    $atime,
    $mtime,
    $ctime,
    $blksize,
    $blocks) = stat ($filename);

#
# Step 3: Turn the action off
#
my %changes = ( 'enabled' => 0, );
($ok, $badkeys, @info) = $cce->set($oid, '',  \%changes);
if (! $ok) {
	failed($cce, $oid,
	     "$testname failed: could not modify Schedule object $oid.");
}

#
# Step 4: Wait to make sure the test file does not get changed for 100 seconds.
#
$retries = 20;
unlink $filename;
while ($retries && ! -f $filename) {
	sleep(5);
	$retries--;
}
if ($retries != 0 && -f $filename) {
	failed($cce, $oid, "$testname failed: action executed twice.\n");
}

# Close the connection to CCE and exit.
$cce->destroy($oid);
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
