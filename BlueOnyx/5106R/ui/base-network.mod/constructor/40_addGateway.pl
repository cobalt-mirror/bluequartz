#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: 40_addGateway.pl 1165 2008-06-30 03:28:18Z mstauber $

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
    # 1st try (with quotes):
    open(CONF, $conf) || die "Can't read configfile: $conf\n";
    while (<CONF>) {
	next unless /^GATEWAY=\"(.*)\"/;
	$gateway = $1;
    }
    close(CONF);

    # 2nd try (without quotes):
    if (!$gateway) {
        open(CONF, $conf) || die "Can't read configfile: $conf\n";
	while (<CONF>) {
	    next unless /^GATEWAY=\s*(\S+)/;
	    $gateway = $1;
	}
	close(CONF);
    }
}

if (!$gateway) {
    $DEBUG && print STDERR "Gateway setting isn't found\n";
    # Attempt to determine Gateway through other means:
    if ( ! -f "/proc/user_beancounters" ) {
	# Not running on OpenVZ, so check the route to find the Gateway:
    	my $data = `$Network::ROUTE -n|grep '^0\.0\.0\.0'`;
    	if ($data =~ /0\.0\.0\.0\s+((\d+).(\d+).(\d+).(\d+))/) {
		$gateway = $1;
    	}
    }
    else {
	# We're on OpenVZ, so set the default OpenVZ gateway:
	$gateway = "192.0.2.1";
        system("/bin/cat /etc/sysconfig/network |/bin/grep -v ^GATEWAY > /etc/sysconfig/network.bak");
        system("/bin/mv /etc/sysconfig/network.bak /etc/sysconfig/network");
        system("/bin/echo 'GATEWAY=\"192.0.2.1\"' >> /etc/sysconfig/network");
        system("/etc/init.d/network restart > /dev/null");
    }
}

($DEBUG && $gateway) && print STDERR "Gateway is $gateway\n";

$cce->set($oids[0], '', {'gateway' => $gateway});
$cce->bye();
exit 0;
