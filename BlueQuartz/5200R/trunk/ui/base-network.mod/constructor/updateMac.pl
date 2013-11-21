#!/usr/bin/perl -w -I/usr/sausalito/perl -I. -I/usr/sausalito/handlers/base/network
# $Id$
# Copyright 2007 Project BlueQuartz.  All rights reserved.

use strict;
use CCE;
use Network;

my $DEBUG = 0;

my $cce = new CCE;
$cce->connectuds();


my @oids = $cce->find('Network');
foreach my $oid (@oids) {
  my ($ok, $obj) = $cce->get($oid);
  my $device = $obj->{'device'};

  my $data = `$Network::IFCONFIG $device`;

  my $hex = '[0-9a-fA-F]{2,}';
  if ($data =~ m/HWaddr ($hex:$hex:$hex:$hex:$hex:$hex)/) {
    my $mac = $1;
    $DEBUG && print STDERR "mac address for device $device: $mac\n";
    my ($ok) = $cce->set($oid, '', { 'mac' => $mac });
  }
}

$cce->bye('SUCCESS');
exit(0);

