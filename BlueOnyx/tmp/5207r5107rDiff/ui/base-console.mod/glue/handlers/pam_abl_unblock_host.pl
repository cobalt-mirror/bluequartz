#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: pam_abl_unblock_host.pl, v1.0.0-1 Fri 07 Aug 2009 08:47:06 AM CEST mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2009-2011 Team BlueOnyx. All rights reserved.

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
    if ($fail_host->{"host_remove"}) {
	$hostname = $fail_host->{"host_fqdn"};
	$hostip = $fail_host->{"host_ip"};
	system("/usr/bin/pam_abl --okhost=$hostname");
	system("/usr/bin/pam_abl --okhost=$hostip");
    	system("/etc/init.d/xinetd stop");
    	system("/bin/rm -f /var/log/proftpd/ban.tab");
    	system("/etc/init.d/xinetd start");
    }
}

$cce->bye('SUCCESS');
exit(0);
