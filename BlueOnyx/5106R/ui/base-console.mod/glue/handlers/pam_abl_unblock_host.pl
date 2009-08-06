#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: pam_abl_unblock_host.pl, v1.0.0-0 Thu 06 Aug 2009 05:34:12 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009 Team BlueOnyx. All rights reserved.

# This handler is run whenever someone wants to unblock a host through the GUI.

use CCE;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectfd();

# Get our events from the event handler stack:
$oid = $cce->event_oid();

# Get Object pam_abl_settings for from CODB:
($ok, $fail_host) = $cce->get($oid);

# Make sure to only trigger on modify:
if ($cce->event_is_modify()) {
    if (($fail_host->{"host_remove"}) && ($fail_host->{"host"})) {
	$hostname = $fail_host->{"host"};
	system("/usr/bin/pam_abl --okhost=$hostname");
    }
}

$cce->bye('SUCCESS');
exit(0);
