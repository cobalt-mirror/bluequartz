#!/usr/bin/perl -I/usr/sausalito/perl
#
# Copyright (c) 2000 Cobalt Networks
# Copyright (c) 2004 Turbolinux, inc.
# Copryight (c) 2011 Team BlueOnyx

# disables AM on non-RAID system

use CCE;
$cce = new CCE;

$mdstat = "/proc/mdstat";

# Start sane - assume no RAID:
$on = "0";

if ( -e $mdstat) {
    open(DATA, $mdstat) || die("Could not open $mdstat!");
    @raw_data=<DATA>;
    close(DATA);

    $foundraid = "0";

    foreach $line (@raw_data) {
        chomp($line);
        if ($line =~ /^md(.*)/ ) {
                $foundraid++;
        }
    }

    if ($foundraid > "0") {
        $on = "1";
    }
}
else {
    # No RAID:
    $on = "0";
}

$cce->connectuds();
my @oids = $cce->find ('ActiveMonitor');
if ($#oids > -1) {
    $cce->set($oids[0], 'DiskIntegrity', { "monitor" => $on });
}
$cce->bye();
exit 0;

