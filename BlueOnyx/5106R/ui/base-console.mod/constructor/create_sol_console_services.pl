#!/usr/bin/perl
# $Id: create_sol_console_services.pl,v 1.0 Thu 02 Jul 2009 12:56:09 AM CEST
# Copyright 2006-2009 Solarspeed Ltd., All rights reserved.
# Copyright 2009 Team BlueOnyx, All rights reserved.
#
# Creates required objects used to register services.

use lib qw(/usr/sausalito/perl);
use CCE;

my $cce = new CCE;

$cce->connectuds();

# make sure the SOL_Console object exists
if (not scalar($cce->find('SOL_Console'))) {
	my ($ok) = $cce->create('SOL_Console');

	if (not $ok) {
		$cce->bye('FAIL', 'System object SOL_Console does not exist!');
		exit(1);
	}
}

$cce->bye('SUCCESS');
exit(0);

