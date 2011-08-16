#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: dnsMXmassChange.pl, Thu Mar 19 17:21:57 2009 mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.

use CCE;
my $cce = new CCE;

$switch_o = $ARGV[0];
$oldmx = $ARGV[1];
$switch_n = $ARGV[2];
$newmx = $ARGV[3];

print "\n";
print "dnsMXmassChange.pl V2.0\n";
print "=======================\n\n";
print "Author: Michael Stauber\n\n";
print "This script can change the MX host of all DNS records from one host to another.\n\n";

if (!$switch_o || !$switch_n || !$oldmx || !$newmx) {
        print "To use it, run it with the followinng parameters:\n\n";
        print $0 . " -o old-MX-host -n new-MX-host\n\n";
	print "Example: \n\n";
        print $0 . " -o www.blueonyx.it -n mail.blueonyx.it\n\n";
	print "You can also change each and any MX record to the same one (caution!) using this syntax:\n\n";
        print $0 . " -o ALL -n mail.blueonyx.it\n\n";
        exit 1;
} elsif (($switch_o == "-o") && ($oldmx == "ALL") && ($switch_n == "-n") && $newmx) {
	$changeall = 1;
        print "Changing each and any MX records to $newmx ... \n\n";
	$cce->connectuds();
	&feedthemonster;
	&setdirty;
	$cce->bye('SUCCESS');
	exit(0);
} elsif (($switch_o == "-o") && $oldmx && ($switch_n == "-n") && $newmx) {
	$changeall = 0;
        print "Changing all MX records currently using $oldmx to $newmx ... \n\n";
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
    if ( $changeall == '0') {
        (@oids) = $cce->find('DnsRecord', {
                    'mail_server_name' => $oldmx, 'type' => "MX"
                  });
        for $object (@oids) {
                ($ok, $rec) = $cce->get($object);
                if ($rec->{'mail_server_name'} ne "") {
                        print "Changing MX for " . $rec->{'mail_server_name'} . " to " . $newmx . "\n";
                }
                ($ok) = $cce->set($object, '',{
                    'mail_server_name' => $newmx
                });
        }
    }
    else {
        (@oids) = $cce->find('DnsRecord', { 'type' => "MX" });
        for $object (@oids) {
                ($ok, $rec) = $cce->get($object);
                if ($rec->{'mail_server_name'} ne "") {
                        print "Changing MX for " . $rec->{'mail_server_name'} . " to " . $newmx . "\n";
                }
                ($ok) = $cce->set($object, '',{
                    'mail_server_name' => $newmx
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
