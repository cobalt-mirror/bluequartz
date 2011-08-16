#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: dellist_check.pl,v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.

use lib qw( /usr/sausalito/perl );
use CCE;
my $cce = new CCE;
$cce->connectfd();

my $old = $cce->event_old();

if ($old->{group}) {
	$cce->warn("[[base-mailman.CannotDeleteGroupMaillist]]");
	$cce->bye("FAIL");
	exit(1);
}

$cce->bye("SUCCESS");
exit(0);

