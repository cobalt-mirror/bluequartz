#!/usr/bin/perl -I/usr/sausalito/perl
# Author: Brian N. Smith
# Copyright 2006, NuOnce Networks, Inc. All rights reserved.
# $Id: mysql-del-backup.pl,v 1.0 2006/08/09 10:22:00 Exp $

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
if (!defined($oids[0])) {
        exit 0;
}

unlink "/usr/sausalito/ui/web/base/mysql/mysql-dump.sql";

$cce->bye('SUCCESS');
exit 0;
