#! /usr/bin/perl -I /usr/sausalito/perl

# $Id: 01_test.pl,v 1.2 2001/08/10 01:13:44 bservies Exp $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#

#
# Description:
#	This test attempts to create a basic Schedule object, modify a
# property, and delete it.
#
use File::Basename;
use Cwd;
use CCE;

my $cce = new CCE;
$cce->connectuds();

# Determine the base name of this test
my ($testname, $path, $suffix) = fileparse($0, '');
my $dir = cwd();
$action = $dir . '/test_action --name=' . $testname;

# The properties to use when creating the object
my $class = 'Schedule';
my %properties = (
	'name' => $testname,
	'enabled' => 0,
	'start_time' => time(),
	'interval' => 'minute',
	'action' => $action,
);

# Attempt to create an object
my ($ok, $badkeys, @info) = $cce->create($class, \%properties);
if (! $ok) {
	$msg = join "\n", @info;
	die $testname . ' failed: could not create Schedule object.' .
	    "\n$msg\n";
}


# Attempt to delete the object just created
my $oid = $cce->oid(); 
($ok, @info) = $cce->destroy($oid);
if (! $ok) {
	$msg = join "\n", @info;
	die $testname . ' failed: could not destroy Schedule object ' .
	   "$oid\n$msg\n";
}

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
