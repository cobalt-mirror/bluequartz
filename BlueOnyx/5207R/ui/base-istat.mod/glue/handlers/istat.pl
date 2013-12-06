#!/usr/bin/perl -w -I /usr/sausalito/perl
# $Id: $
# Copyright 2010, Project BlueQuartz. All rights reserved.
# Configure istat.conf

use CCE;
use Sauce::Service;
use Sauce::Util;


my $iStatConf = '/etc/istat.conf';

my $cce = new CCE('Namespace' => 'iStat');
$cce->connectfd();

my $iStat = $cce->event_object();

if (!Sauce::Util::editfile($iStatConf, *update_istat_conf, $iStat)) {
	$cce->warn("[[base-istat.errorWritingConfFile]]");
}

if ($iStat->{'enabled'}) {
	service_run_init('istatd', 'restart');
}

$cce->bye('SUCCESS');
exit 0;


sub update_istat_conf
{
	my ($fin, $fout, $obj) = @_;

	my $serverCode = $obj->{'serverCode'};
	my $networkPort = $obj->{'networkPort'};

	while (<$fin>) {
		if (/server_code/) {
			print $fout "server_code	$serverCode\n";
		} elsif (/network_port/) {
			print $fout "network_port	$networkPort\n";
		} else {
			print $fout $_;
		}
	}
	return 1;
}

