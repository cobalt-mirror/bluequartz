#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: pam_abl_unblock_user.pl, v1.0.0-0 Thu 06 Aug 2009 05:41:28 AM CEST mstauber Exp $
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
($ok, $fail_user) = $cce->get($oid);

# Make sure to only trigger on modify:
if ($cce->event_is_modify()) {
    if (($fail_user->{"user_remove"}) && ($fail_user->{"username"})) {
	$username = $fail_user->{"username"};
	system("/usr/bin/pam_abl --okuser=$username");
    }
}

$cce->bye('SUCCESS');
exit(0);
