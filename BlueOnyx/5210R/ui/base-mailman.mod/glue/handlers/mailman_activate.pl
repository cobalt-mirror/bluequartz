#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: mailman_activate.pl Sun 24 Apr 2011 11:53:53 PM CEST $
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# Enables and starts (or stops and disables) MailMan

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
if (@oids) {
    my ($ok, $obj) = $cce->get($oids[0], 'MailListStatus');
    my $enabled = $obj->{enabled};

    # If MailMan is enabled, start the service:
    if ($enabled eq "1") {
	system("/sbin/chkconfig --level 2345 mailman on");
	system("/sbin/service mailman restart > /dev/null 2>&1");
    }
    else {
	system("/sbin/chkconfig --level 2345 mailman off");
	system("/sbin/service mailman stop > /dev/null 2>&1");
    }

    # While we're switching MailMan on or off, we also tell ActiveMonitor
    # if it should  monitor this component:
    @amOIDS = $cce->find('ActiveMonitor');
    if (@amOIDS) {
        $cce->set($amOIDS[0], 'MAILMAN', {
        'enabled' => $enabled
        });
    }
}
else {
  # we have major problems if the System object doesn't exist,
  # some code to crash out with an error and create panic
  print STDERR "No System object in CCE!!!!\n";
  exit 0;
}

$cce->bye('SUCCESS');
exit(0);

