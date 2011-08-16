#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: dnsMassChange.pl, Thu Mar 19 17:21:57 2009 mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.

use CCE;
my $cce = new CCE;

$switch_o = $ARGV[0];
$old_ip = $ARGV[1];
$switch_n = $ARGV[2];
$new_ip = $ARGV[3];

print "\n";
print "dnsMassChange.pl V2.0\n";
print "======================\n\n";
print "Author: Michael Stauber\n\n";
print "This script can change all DNS records with a certain old IP to a specified new IP.\n\n";

if (!$switch_o || !$switch_n || !$old_ip || !$new_ip) {
        print "To use it, run it with the followinng parameters:\n\n";
        print $0 . " -o old-ip -n new-ip\n\n";
        exit 1;
} elsif ($switch_o && $old_ip && $switch_n && $new_ip) {
        print "Changing all DNS records with IP $old_ip to the new IP $new_ip ... \n\n";
	$cce->connectuds();
	&feedthemonster;
	&setdirty;
	$cce->bye('SUCCESS');
	exit(0);
} else {
        print "Aborting without doing anything ...\n\n";
        exit 1;
}

sub feedthemonster {
    (@oids) = $cce->find('DnsRecord', { 'ipaddr' => $old_ip });
    if ($#oids == -1) {
        print "No DNS records found which have the IP address " . $old_ip . "\n\n";
        exit(1);
    }
    else {
        for $object (@oids) {
		($ok, $rec) = $cce->get($object);
		if ($rec->{'hostname'} ne "") {
            		print "Changing DNS for " . $rec->{'hostname'} . "." . $rec->{'domainname'} . " to the IP " . $new_ip . "\n";
            	}
		else {
            		print "Changing DNS for " . $rec->{'domainname'} . " to the IP " . $new_ip . "\n";
		}
                ($ok) = $cce->set($object, '',{
                    'ipaddr' => $new_ip
                });
        }
    }
}

sub setdirty {
    # Get 'System' details:
    @system_main = $cce->find('System');
    if (!defined($system_main[0])) {
        print "Sorry, no 'System' object found in CCE!\n";
        exit(1);
    }
    else {
        # Build Records:
        #($ok, $my_system_main) = $cce->get($system_main[0]);
        ($ok) = $cce->set($system_main[0], 'DNS', {
            'dirty' => time()
        });
    }
}

