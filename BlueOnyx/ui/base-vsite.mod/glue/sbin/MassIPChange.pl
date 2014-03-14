#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: MassIPChange.pl, Thu Mar 13 2014 mstauber Exp $
# Copyright 2006-2014 Michael Stauber, Solarspeed.net
# All rights reserved.

use CCE;
my $cce = new CCE;

$switch_o = $ARGV[0];
$old_ip = $ARGV[1];
$switch_n = $ARGV[2];
$new_ip = $ARGV[3];

print "\n";
print "MassIPChange.pl V2.0\n";
print "======================\n\n";
print "Author: Michael Stauber\n\n";
print "This script can change all VSitess with a certain old IP to a specified new IP.\n\n";

if (!$switch_o || !$switch_n || !$old_ip || !$new_ip) {
        print "To use it, run it with the followinng parameters:\n\n";
        print $0 . " -o old-ip -n new-ip\n\n";
        exit 1;
} elsif ($switch_o && $old_ip && $switch_n && $new_ip) {
        print "Changing all Vsites with IP $old_ip to the new IP $new_ip ... \n\n";
	$cce->connectuds();
	&feedthemonster;
	$cce->bye('SUCCESS');
	exit(0);
} else {
        print "Aborting without doing anything ...\n\n";
        exit 1;
}

sub feedthemonster {
    (@oids) = $cce->find('Vsite', { 'ipaddr' => $old_ip });
    if ($#oids == -1) {
        print "No Vsite(s) found which have the IP address " . $old_ip . "\n\n";
        exit(1);
    }
    else {
        for $object (@oids) {
            ($ok, $rec) = $cce->get($object);
            print "Changing IP for " . $rec->{'fqdn'} . " to the IP " . $new_ip . "\n";
            ($ok) = $cce->set($object, '',{
                'ipaddr' => $new_ip
                });
            }
    }
}
