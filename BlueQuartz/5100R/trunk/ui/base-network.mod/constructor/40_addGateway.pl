#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: 40_addGateway.pl 676 2005-12-23 13:18:39Z shibuya $

# author: shibuya@alpha.or.jp

# this needs use lib because it isn't a handler
use lib qw(/usr/sausalito/handlers/base/network);
use CCE;
use Network;

my $DEBUG = 0;

my $errors = 0;

my $conf = '/etc/sysconfig/network';
my $gateway = '';

my $cce = new CCE;
$cce->connectuds();

my @oids = $cce->find('System');
if (not @oids) {
    $cce->bye('FAIL');
    exit 1;
}

my ($ok, $obj) = $cce->get($oids[0]);
unless ($ok and $obj) {
    $cce->bye('FAIL');
    exit 1;
}

if (-f $conf) {
    open(CONF, $conf) || die "Can't read configfile: $conf\n";
    while (<CONF>) {
	next unless /^GATEWAY=\s*(\S+)/;
	$gateway = $1;
    }
}
close(CONF);

if (!$gateway) {
    $DEBUG && print STDERR "Gateway setting isn't found\n";
    my $data = `$Network::ROUTE -n|grep '^0\.0\.0\.0'`;
    if ($data =~ /0\.0\.0\.0\s+((\d+).(\d+).(\d+).(\d+))/) {
	$gateway = $1;
    }
}

($DEBUG && $gateway) && print STDERR "Gateway is $gateway\n";

$cce->set($oids[0], '', {'gateway' => $gateway});
$cce->bye();
exit 0;
