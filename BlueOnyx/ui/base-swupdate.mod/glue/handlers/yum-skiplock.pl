#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# Author: Michael Stauber 
# Copyright 2006-2007, Stauber Multimedia Design  All rights reserved. 
# $Id: yum-skip-lock.pl, v1.0 Tue 13 Jan 2009 11:44:32 PM CET Exp $   

use CCE;
use Sauce::Util;
use Sauce::Service;

my $cce = new CCE;
$cce->connectfd();

my @oids = $cce->find('System');
if (!defined($oids[0])) {
	print STDERR "Sorry, no System object in CCE found!\n";
	exit 0;
}

my $yumcheck  = "/tmp/yum.check-update";
my $yumupdate = "/tmp/yum.update";

if (-f $yumcheck) {
    Sauce::Util::chmodfile(00444, $yumcheck);
}
if (-f $yumupdate) {
    Sauce::Util::chmodfile(00444, $yumupdate);
}

$cce->bye('SUCCESS');
exit(0);

