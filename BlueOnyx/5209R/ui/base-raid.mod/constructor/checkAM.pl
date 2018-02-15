#!/usr/bin/perl -I/usr/sausalito/perl
#
# Copyright (c) 2000 Cobalt Networks
# Copyright (c) 2004 Turbolinux, inc.

# disables AM on non-RAID system

use CCE;
my $cce = new CCE;

if (-f "/proc/mdstat")  {
	my $ret = `grep -q raid /proc/mdstat`;
	my $on = $? >> 8;
}
else {
	my $on = "0";
}

$cce->connectuds();
my @oids = $cce->find ('ActiveMonitor');
if ($#oids > -1) {
	if ($on == "1") {
  		$cce->set($oids[0], 'RAID', {"monitor" => "1"});
  		$cce->set($oids[0], 'SMART', {"monitor" => "1"});
  		$cce->set($oids[0], 'DMA', {"monitor" => "1"});
  		$cce->set($oids[0], 'DiskIntegrity', {"monitor" => "1"});
  	}
  	else {
  		$cce->set($oids[0], 'RAID', {"monitor" => "0"});
  		$cce->set($oids[0], 'SMART', {"monitor" => "0"});
  		$cce->set($oids[0], 'DMA', {"monitor" => "0"});
  		$cce->set($oids[0], 'DiskIntegrity', {"monitor" => "0"});
  	}
}
$cce->bye();
exit 0;
