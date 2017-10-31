#!/usr/bin/perl -w -I /usr/sausalito/perl
# $Id: $
# Copyright 2010, Project BlueQuartz. All rights reserved.
# toggles the iStat server on and off

use CCE;
use Sauce::Service;

my $cce = new CCE('Namespace' => 'iStat');
$cce->connectfd();

my $iStat = $cce->event_object();

service_toggle_init('istatd', $iStat->{'enabled'});

$cce->bye('SUCCESS');
exit(0);

