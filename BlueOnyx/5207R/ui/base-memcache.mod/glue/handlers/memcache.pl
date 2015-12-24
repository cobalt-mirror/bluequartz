#!/usr/bin/perl -w -I /usr/sausalito/perl
# Copyright 2015, Smack, Inc. All rights reserved.
# Configure /etc/sysconfig/memcached

use CCE;
use Sauce::Service;
use Sauce::Util;


my $sysconfig = '/etc/sysconfig/memcached';

my $cce = new CCE('Namespace' => 'memcache');
$cce->connectfd();

my $obj = $cce->event_object();
my $old = $cce->event_old();

if (!Sauce::Util::editfile($sysconfig, *update_sysconfig, $obj)) {
	$cce->warn("[[base-memcache.errorWritingConfFile]]");
}

if ($obj->{'enabled'} && ($old->{'cachesize'} != $obj->{'cachesize'})) {
	service_run_init('memcached', 'restart');
}

$cce->bye('SUCCESS');
exit 0;


sub update_sysconfig
{
	my ($fin, $fout, $obj) = @_;

	my $cachesize = $obj->{'cachesize'};

	while (<$fin>) {
		if (/CACHESIZE/) {
			print $fout "CACHESIZE=\"$cachesize\"\n";
		} else {
			print $fout $_;
		}
	}
	return 1;
}

