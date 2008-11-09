#!/usr/bin/perl -w

use strict;
use lib qw( /usr/sausalito/perl /usr/sausalito/handlers/base/modem );
use CCE;
use I18n;
use Modem;

my $cce = new CCE;
my $status = 'offNow';

$cce->connectuds();
$cce->authkey($ENV{CCE_USERNAME}, $ENV{CCE_SESSIONID});

my ($sysoid) = $cce->find("System");

my ($ok, $system) = $cce->get($sysoid);

($ok, my $modem) = $cce->get($sysoid, "Modem");

$cce->bye();

if (not $ok) {
	print 'cantGetModem';
	exit(1);
}

# check for lock on serial port
if (-f "/var/lock/LCK..$modem->{port}") {
	# a lock exists, so check if the pid in the lock is a running pppd
	my $lock_file = `/bin/cat /var/lock/LCK..$modem->{port}`; 

	$lock_file =~ /(\d+)/;

	# if lock isn't stale do a network test
	if (`/usr/bin/pstree $1` =~ /^pppd/) {
		if(Modem::test_net('ppp0')) {
			$status = 'onNow';
		}
	} else {
		# remove stale lock file
		unlink '/var/lock/LCK..$modem->{port}';
	}
}

# output current status back to ui
print $status;

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
