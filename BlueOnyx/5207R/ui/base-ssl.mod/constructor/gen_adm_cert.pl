#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: gen_adm_cert.pl
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
# FIXED: This actually removes current time from bug_time in y2038 and subtracts 20 days
#        to avoid any strange race conditions. I REALLY HOPE WE DON'T USE 32-bit Linux
#        machines with the y2038 present when the time comes... PBaltz was wrong thouch
#        to hope for a quick fix! :) / Rickard Osser <rickard.osser@bluapp.com>
#
my $current_time = time();
my $bug_time = (2 ** 31) - 100;  # T-100 seconds to avoid race conditions
my $difference = $bug_time - $current_time;
my $days_valid = int($difference / 86400)- 20; # T - 20 days to avoid race conditions.

# if the clock in the server by any chance is set to something that is past, hardcode 9995 days as that won't break anything.
if ( $days_valid > 9995 ) {
    $days_valid = 9995;
}
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
			'orgName' => 'Project BlueOnyx'
		  });
$cce->bye();
exit(0);
# 
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 