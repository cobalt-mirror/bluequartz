#!/usr/bin/perl -w -I/usr/sausalito/perl
# Author: Brian N. Smith, Michael Stauber
# Copyright 2006-2007, NuOnce Networks, Inc.  All rights reserved.
# Copyright 2006-2007, Stauber Multimedia Design  All rights reserved. 
# $Id: yum-checker.pl, v1.0 2007/12/20 9:02:00 Exp $

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
if (!defined($oids[0])) {
	exit 0;
}

my ($ok, $yum) = $cce->get($oids[0], "yum");

if ( $ok ) {
	`/usr/sausalito/handlers/base/swupdate/yum-checker.sh`;
	$cce->bye('SUCCESS');
	exit(0);
}
