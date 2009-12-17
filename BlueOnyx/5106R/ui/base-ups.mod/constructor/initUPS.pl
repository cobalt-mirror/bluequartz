#!/usr/bin/perl -w -I/usr/sausalito/perl

# Author: Rickard Osser <rickard.osser@bluapp.com>
# Copyright 2009, Bluapp AB.  All rights reserved.
#use strict;
#use diagnostics;
use CCE;
use Sauce::Util;
use Sauce::Service;

# Globals.

my $drivers = "/usr/share/nut/driver.list";
my ($manufacturer, $model, $modelExtra, $driver, $dtemp1, $dtemp2);
my $UPSDUSERS = '/etc/ups/upsd.users';
my $UPSMON = '/etc/ups/upsmon.conf';

my $cce = new CCE;
$cce->connectuds();

# Set wake on lan on eth0 to magic-package
system("/sbin/ethtool -s eth0 wol g");

# Add bluapp monitor user to UPS
my $ret = Sauce::Util::editfile($UPSDUSERS, *editUPSDUSERS);

# Change shutdown command to our own to handle wake-on-lan.
$ret = Sauce::Util::editfile($UPSMON, *editUPSMON);


# Add new drivers if needed
# We should change already added drivers but that is for the next gen!
open(DRIVERS, "<$drivers");

while(<DRIVERS>) {
    if( !/^#/ && !/^[ \n]/ && !$_ eq "" ) {
	( $manufacturer, $model, $modelExtra, $driver) = split(/"\t+|"  +|" "/, $_);
	$manufacturer =~ s/"//g;
	$model =~ s/"//g;
	$modelExtra =~ s/"//g;
	$driver =~ s/"//g;
	$driver =~ s/ \(.+\)//g;
	chomp($driver);
	if( $driver =~ / or /) {
	    ($dtemp1, $dtemp2) = split(" or ", $driver); 
	    $driver = $dtemp2;
	}
	if ( (
	      $manufacturer eq "APC" || 
	      $manufacturer eq "Eaton" ||
	      $manufacturer eq "Fiskars"
	      )
	     && $driver
	     ) {
	    my @foundDrivers = $cce->find("upsDrivers", { 
		'manufacturer' => $manufacturer,
		'modelName' => $model
		});
	    if(!@foundDrivers) {
		my $success = $cce->create("upsDrivers", {
		    'manufacturer' => "$manufacturer",
		    'modelName' => "$model",
		    'modelExtra' => "$modelExtra",
		    'upsDriver' => "$driver"
		    });
	    }
	}
    }
}

$cce->bye();


exit 0;


sub editUPSDUSERS
{
    my ($fin, $fout) = (shift, shift);
    my $done;

    foreach my $line (<$fin>) {
	if ($line =~ /^\[upsmon/) {
	    print $fout "$line";
	    $done = 1;
	} else {
	    print $fout "$line";
	}
    }
    if (!$done) {
	print $fout "[upsmon]\n";
	print $fout "password = bluappups\n";
	print $fout "actions = SET\n";
	print $fout "instcmds = ALL\n";
	$done = 1;
    }
    return 1;
}

sub editUPSMON
{
    my ($fin, $fout) = (shift, shift);
    my $done;

    foreach my $line (<$fin>) {
	if ($line =~ /^SHUTDOWNCMD/) {
	    print $fout 'SHUTDOWNCMD "/usr/bin/ups-shutdown"\n'
	    }
	elsif ($line =~ /^# MONITOR myups\@localhost 1 upsmon pass master/) {
	       print $fout 'MONITOR myups@localhost 1 upsmon pass master\n'
	   } else {
	       print $fout "$line";
	   }
    }
    return 1;
}
