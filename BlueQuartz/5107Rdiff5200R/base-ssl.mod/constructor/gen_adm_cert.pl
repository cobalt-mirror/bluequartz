#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: gen_adm_cert.pl,v 1.2.2.1 2002/01/14 20:52:30 pbaltz Exp $
#
# Make sure an ssl cert exists for the admin server.

use strict;
use lib qw(/usr/sausalito/perl);
use CCE;

if (-e '/etc/admserv/certs/certificate') {
	exit(0);
}

my $cce = new CCE;
$cce->connectuds();

# find the system object
my ($sys_oid) = $cce->find('System');
my ($ok, $sys) = $cce->get($sys_oid);

#
# FIXME: This checks to make sure the number of days valid for the default
# 	 cert doesn't push past the y2038 bug barrier (rollover because time
#	 is only 4 bytes in C).  This should be removed if/when the y2038 bug
#	 is fixed.  This will occur in about 9 years or so as of 1/14/2002, and
#	 more than likely this script will no longer be in use.  Hey, I was
#	 bored.
#
my $days_valid = 9999;
my $current_time = time();
my $expire_time = $current_time + ($days_valid * 24 * 60 * 60);
my $bug_time = (2 ** 31) - 100;  # T-100 seconds to avoid race conditions
my $difference = $bug_time - $expire_time;

if ($difference < 0) {
	# need to correct days valid
	$days_valid += int($difference / (24 * 60 * 60));
}

# set up ssl
($ok) = $cce->set($sys_oid, 'SSL',
		  {
			'createCert' => time(),
			'daysValid' => $days_valid,
			'country' => 'SS',
			'state' => 'SS',
			'city' => 'Generic',
			'orgName' => 'Project BlueQuartz'
		  });
$cce->bye();
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
