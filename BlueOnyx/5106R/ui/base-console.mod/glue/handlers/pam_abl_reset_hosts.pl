#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: pam_abl_reset_hosts.pl, v1.0.0-0 Thu 06 Aug 2009 06:24:22 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009 Team BlueOnyx. All rights reserved.

# This handler is run whenever someone wants to remove all host blocks through the GUI

use CCE;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectfd();

# Get our events from the event handler stack:
$oid = $cce->event_oid();

# Get Object pam_abl_settings for from CODB:
($ok, $worker) = $cce->get($oid);

# Make sure to only trigger on modify:
if ($cce->event_is_modify()) {
    system('/usr/bin/pam_abl --okhost=*');
}

$cce->bye('SUCCESS');
exit(0);
