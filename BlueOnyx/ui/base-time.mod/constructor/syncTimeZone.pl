#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: setTimeZone.pl Sat 22 Aug 2009 06:35:13 AM EDT mstauber $
# Copyright 2008-2009 Project BlueOnyx., All rights reserved.

use Sauce::Util;
use CCE;

my $cce=new CCE;
$cce->connectuds();

my($oid)=$cce->find("System");
my($ok,$time_obj)=$cce->get($oid,"Time");

my $localtime = '/etc/localtime';
my $clock = '/etc/sysconfig/clock';

# set the timezone first
my $zone = $time_obj->{timeZone};

# Obnoxious glibc UTC sign swap
if ($zone =~ /GMT\+\d+/) {
	$zone =~ s/\+/\-/;
} elsif ($zone =~ /GMT\-\d+/) {
	$zone =~ s/\-/\+/;
}

my $link = '../usr/share/zoneinfo/' . $zone;
if ($zone and (readlink($localtime) ne $link)) {
	unlink('/etc/localtime');
	symlink($link, '/etc/localtime');
}

# update /etc/sysconfig/clock
if ($zone) {
    system("/bin/echo ZONE=\"$zone\" > /etc/sysconfig/clock");
    system("/bin/echo UTC=false >> /etc/sysconfig/clock");
    system("/bin/echo ARC=false >> /etc/sysconfig/clock");
}

$cce->bye("SUCCESS");
exit 0;
