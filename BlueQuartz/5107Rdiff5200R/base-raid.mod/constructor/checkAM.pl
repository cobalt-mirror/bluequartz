#!/usr/bin/perl -I/usr/sausalito/perl
#
# Copyright (c) 2000 Cobalt Networks
# Copyright (c) 2004 Turbolinux, inc.

# disables AM on non-RAID system

use CCE;
my $cce = new CCE;

my $ret = `grep -q raid /proc/mdstat`;
my $on = $? >> 8;

$cce->connectuds();
my @oids = $cce->find ('ActiveMonitor');
if ($#oids > -1) {
  $cce->set($oids[0], 'DiskIntegrity', {"monitor" => (($on == 1) ? 0 : 1)});
}
$cce->bye();
exit 0;

