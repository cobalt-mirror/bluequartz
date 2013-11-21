#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email/
# $Id: zpush.pl Sat 10 Apr 2010 07:01:10 AM CEST mstauber $
# Copyright 2010, Team BlueOnyx, All rights reserved.

use Sauce::Service;
use CCE;
use Email;

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/zpush.enable");
$DEBUG && warn `date`;

my $cce = new CCE( Namespace => 'Email',
                      Domain => 'base-email' );

$cce->connectfd();

my $old_obj = $cce->event_old();
my $obj = $cce->event_object();

if ($obj->{enableZpush}) {
    system("/bin/rm -f /usr/sausalito/ui/web/z-push/.disabled");
}
else {
    system("/bin/touch /usr/sausalito/ui/web/z-push/.disabled");
}

$cce->bye("SUCCESS");
exit(0);

