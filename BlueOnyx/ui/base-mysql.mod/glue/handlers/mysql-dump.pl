#!/usr/bin/perl -I/usr/sausalito/perl
# Authors: Brian N. Smith and Michael Stauber
# Copyright 2006, NuOnce Networks, Inc. and Solarspeed Ltd. All rights reserved.
# $Id: mysql-dump.pl,v 1.1 Fri Dec  5 02:11:24 2008 Exp $

use CCE;
use Sauce::Util;
use Sauce::Service;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
if (!defined($oids[0])) {
	exit 0;
}

my ($ok, $nuMySQL) = $cce->get($oids[0], "mysql");

$user = $nuMySQL->{'username'};
$pass = $nuMySQL->{'password'};

my @moids = $cce->find('MySQL');
if (!defined($moids[0])) {
	exit 0;
}

my ($ok, $MySQL) = $cce->get($moids[0], "");

$host = $MySQL->{'sql_host'};
$port = $MySQL->{'sql_port'};

$file = "/usr/sausalito/ui/web/base/mysql/mysql-dump.sql";

if ( $pass eq "-1" ) {
	$output = `/usr/bin/mysqldump --user=$user --add-drop-table --all-databases --create-options --host=$host --port=$sql_port > $file`;
} else {
	$output = `/usr/bin/mysqldump --user=$user --password=$pass --add-drop-table --all-databases --create-options  --host=$host --port=$sql_port > $file`;
}

if (-f $file) {
    system("/bin/chmod 644 $file");
    system("/bin/chown apache:apache $file");
}

$cce->bye('SUCCESS');
exit 0;
