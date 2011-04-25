#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/lib/perl5/5.10.0 -I/usr/lib64/perl5/5.10.0/x86_64-linux-thread-multi -I/usr/lib/perl5/5.10.0/i686-linux-thread-multi
# Copyright 2011 Team BlueOnyx. All rights reserved.
# 
# verify a user passes the ruleMailingLists security check

use CCE;

my $cce = new CCE;
$cce->connectfd();

my $mailman = $cce->event_object();
my ($ok, $oid) = $cce->whoami();
if (!$ok || $oid == -1)
{
    $cce->bye('FAIL');
    exit(1);
}
($ok, my $executor) = $cce->get($oid);
if (!$ok)
{
    $cce->bye('FAIL');
    exit(1);
}

if ($mailman->{site} eq $executor->{site})
{
    $cce->bye('SUCCESS');
}
else
{
    $cce->bye('FAIL');
}

exit(0);
