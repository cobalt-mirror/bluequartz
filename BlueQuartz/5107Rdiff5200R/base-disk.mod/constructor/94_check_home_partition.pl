#!/usr/bin/perl
# $Id: $
# Copyright 2009 Project BlueQuartz, All rights reserved.
#
# / partition will be home partition, if there isn't /home partition.


use strict;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/disk);
use CCE;
use Base::Disk qw(disk_getmounts disk_get_usage_info);
use Base::HomeDir;

my $cce = new CCE('Domain' => 'base-disk');

$cce->connectuds();

my ($oid) = $cce->find("Disk", { 'mountPoint' => '/home' });

if (!$oid) {
    my ($oid) = $cce->find("Disk", { 'mountPoint' => '/' });
    $cce->set($oid, "", { 'isHomePartition' => 1 });
}

$cce->bye('SUCCESS');
exit(0);

