#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id$
# Copyright 2010 Bluapp AB.  All rights reserved.

use strict;
use CCE;

my $cce = new CCE;
$cce->connectuds();


# refresh information
my $unique = time();
(my $sys_oid) = $cce->find('System', '');
$cce->set($sys_oid, "Memory", {refresh => $unique});

$cce->bye('SUCCESS');
exit(0);

