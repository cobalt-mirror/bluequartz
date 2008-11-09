#!/usr/bin/perl
# Author: Brian N. Smith
# Copyright 2007, NuOnce Networks, Inc.  All rights reserved.
# $Id: convert2passwd.pl, v1.00 2007/12/14 09:12:00 bsmith Exp $   

# Generic wrapper.  Run a bash script has a handler, it always fails!

use CCE;

my $script = "/usr/sausalito/handlers/base/user/convert2passwd.sh";
my $cce = new CCE;

$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
if (!defined($oids[0])) {
        exit 0;
}

$output = `$script`;
if ( $output eq "FAILED" ) {
  $cce->warn("[[failure]]");
  $cce->bye("FAIL");
  exit 1;
}

$cce->bye("SUCCESS");
exit 0;
