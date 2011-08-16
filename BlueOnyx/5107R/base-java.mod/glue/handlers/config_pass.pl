#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: config_pass.pl,v 1.0.0 Do 27 Nov 2008 12:40:50 CET mstauber Exp $
# Copyright 2007-2008, Solarspeed Ltd. and NuOnce Networks, Inc., All rights reserved.

my $tomcat_properties = '/etc/tomcat6/tomcat-users.xml';

my $DEBUG = 0;
$DEBUG && warn $0.' '.`date`;

use CCE;
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my @oids = $cce->find('System');
my ($ok, $java) = $cce->get($oids[0], "Java");

if($ok) {
	my $ret = Sauce::Util::editfile($tomcat_properties, *edit_policy,
		$java->{JavaAdminPass});

	unless($ret) {
		$DEBUG && warn "$0 failing, editfile $tomcat_properties, ".
			$java->{JavaAdminPass}." failed.\n";
		# $cce->bye('FAIL');
		# exit(1);
	}
}
else {
	$cce->bye('FAIL');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# Fin

sub edit_policy {
        my ($in, $out, $max) = @_;
	my $maxConnect = "  <user username=\"admin\" password=\"$max\" roles=\"admin,manager\"/>\n";

	while(<$in>) {
		if(/  <user username=\"admin\" password(.+)$/) {
			$DEBUG && warn "* Found: $_, using $maxConnect *\n";
			print $out $maxConnect;
		} else {
			print $out $_;
		}
	}
	return 1;
}

