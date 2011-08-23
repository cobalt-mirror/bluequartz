#!/usr/bin/perl -I/usr/sausalito/perl
#
# Copyright (c) 2000 Cobalt Networks
# Copyright (c) 2004 Turbolinux, inc.

# disables AM on non-RAID system

use CCE;
$cce = new CCE;

if (! -e '/proc/mdstat') {
    $on = "0";
}
else {
    $on = "1";
}

$cce->connectuds();
my @oids = $cce->find ('ActiveMonitor');
if ($#oids > -1) {
  $cce->set($oids[0], 'DiskIntegrity', { "monitor" => $on });
}
$cce->bye();
exit 0;

