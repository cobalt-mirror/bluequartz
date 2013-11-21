#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: fixhosts.pl, v1.0.0 Mon 29 Aug 2011 11:28:26 PM EDT mstauber Exp $
# Copyright 2011 Team BlueOnyx. All rights reserved.

use CCE;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectfd();

system("/usr/sausalito/constructor/base/vsite/fixhosts");

$cce->bye('SUCCESS');
exit(0);


