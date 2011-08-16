#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2006, NuOnce Networks, Inc.  All rights reserved.
# $Id: mysql-runner.pl,v 1.0 2006/08/09 10:23:00 Exp $

use CCE;
use Sauce::Util;
use Sauce::Service;

my $cce = new CCE;
$cce->connectfd();

$oid = $cce->event_oid();
$obj = $cce->event_object();

my @oids = $cce->find('System');
if (!defined($oids[0])) {
        exit 0;
}

my ($ok, $mysql) = $cce->get($oids[0], "mysql");

$enable = $mysql->{'enabled'};


$pid = `/sbin/pidof mysqld`;
chomp($pid);

if ( $enable eq "1" ) {
	if ( ! $pid ) {
		$tmp = Sauce::Service::service_run_init('mysqld', 'start');
		$tmp = Sauce::Service::service_set_init('mysqld', 'on', '345');
	}
} else {
	if ( $pid ) {
		$tmp = Sauce::Service::service_run_init('mysqld', 'stop');
		$tmp = Sauce::Service::service_set_init('mysqld', 'off', '345');
	}
}

sleep 5;

$cce->bye('SUCCESS');
exit 0;
