#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: kill_process.pl v1.1.0.0 Thu 02 Jul 2009 01:00:42 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009 Team BlueOnyx. All rights reserved.

# This handler kills of processes that the user wants to kill.

use CCE;
my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$DEBUG = 0;

my $cce = new CCE('Domain' => 'base-console');

if ($DEBUG == 0) {
    $cce->connectfd();
}
else {
    $cce->connectuds(); # only for debugging
}

if ($DEBUG == 0) {
    ($sys_oidnode) = $cce->find('SOL_Console');
    ($ok, $MainNode) = $cce->get($sys_oidnode);
}

$pid = $MainNode->{kill_pid};

# Kill the process
system("/bin/kill -9 $pid");

$cce->bye('SUCCESS');
exit(0);

