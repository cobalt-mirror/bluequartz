#!/usr/bin/perl -w -I /usr/sausalito/perl
# Copyright 2015, Smack, Inc. All rights reserved.
# toggles the memcached on and off

use CCE;
use Sauce::Service;

my $cce = new CCE('Namespace' => 'memcache');
$cce->connectfd();

my $obj = $cce->event_object();

service_toggle_init('memcached', $obj->{'enabled'});
system('/usr/sausalito/sbin/delayRestartCce.sh &');

$cce->bye('SUCCESS');
exit(0);

