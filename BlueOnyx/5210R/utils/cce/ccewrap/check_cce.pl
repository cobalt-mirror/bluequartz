#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: check_cce.pl

use CCE;
use Time::Out qw(timeout);

$nb_secs = "5";
timeout $nb_secs => sub {
    $cce = new CCE;
    $cce->connectuds();
    @oids = $cce->find('System');
    if (not @oids) {
    	print "FAIL"; 
    	$cce->bye('FAIL');
    	exit 1;
    }
    print "SUCCESS";
    $cce->bye('SUCCESS');
    exit(0);
};

if ($@){
    # operation timed-out
    print "TIMEOUT";
    die "TIMEOUT";
    $cce->bye('TIMEOUT');
    exit 1;
}
$cce->bye('SUCCESS');
exit(0);

