#!/usr/bin/perl -I/usr/sausalito/perl
# Authors: Brian N. Smith and Michael Stauber
# Copyright 2006, NuOnce Networks, Inc. and Solarspeed Ltd.  All rights reserved.
# $Id: mysql-changePass.pl,v 1.2 Fri Dec  5 02:10:37 2008 Exp $

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

my @moids = $cce->find('MySQL');
if (!defined($moids[0])) {
        exit 0;
}

my ($ok, $MySQL) = $cce->get($moids[0], "");

$host = $MySQL->{'sql_host'};
$port = $MySQL->{'sql_port'};

$user = $nuMySQL->{'mysqluser'};
$old = $nuMySQL->{'oldpass'};
$new = $nuMySQL->{'newpass'};

if (( $old eq "-1" ) && ($new ne "")) {
    $cmd = `/usr/bin/mysqladmin --user=$user --host=$host --port=$sql_port password $new`;
} 
elsif (($old != "") && ($new == "")) {
    $cmd = `/usr/bin/mysqladmin --user=$user --host=$host --port=$sql_port --password=$old password ""`;
}
else {
    $cmd = `/usr/bin/mysqladmin --user=$user --host=$host --port=$sql_port --password=$old password $new`;
}

$cce->bye('SUCCESS');
exit 0;
