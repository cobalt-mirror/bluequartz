#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: setTimeZone.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2008 Project BlueOnyx., All rights reserved.

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
my $fn = sub {
	my ($fin, $fout) = (shift,shift);
	my ($text) = (shift);

	while (<$fin>) {
		if(m/^ZONE/) {
			# print out the CCE maintained section
			print $fout "ZONE=\"$text\"\n";
		} else {
			print $fout $_;
		}
	}

	return 1;
};

if (!Sauce::Util::editfile($clock, $fn, $zone)) {
	$cce->warn("[[base-time.errorWritingConfFile]]");
}

$cce->bye("SUCCESS");
exit 0;
