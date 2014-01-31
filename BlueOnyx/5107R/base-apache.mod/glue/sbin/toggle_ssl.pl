#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
#
# $Id: toggle_ssl.pl, v 1.0.0-1 
# Copyright 2006-2014 Team BlueOnyx. All rights reserved.
#
# Walks through all SSL enabled Vsites and toggles SSL off and then back on.
# This forces a rewrite of the Apache config file for the Vsite in question.
# This script was added to deal with the fallout from having both ssl_perl.conf
# and glue/handlers/virtual_host.pl add SSL related VirtualHost containers.
#
# Usage:
#
# Simply run this script once. Running it multiple times will do no harm, though.

use CCE;
my $cce = new CCE;
$cce->connectuds();

# Root check:
my $id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!\n";

    $cce->bye('FAIL');
    exit(1);
}

# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite', { 'SSL.enabled' => 1 });

print "Going through all SSL enabled Vsites to toggle SSL off and then back on: \n";

# Walk through all Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

    print "Processing SSL enabled Vsite: $my_vsite->{fqdn} \n";
    print "Setting SSL to 'off'. \n";
    ($ok) = $cce->set($vsite, 'SSL', {
        'enabled' => '0'
       });
    print "Setting SSL to 'on'. \n";
    ($ok) = $cce->set($vsite, 'SSL', {
        'enabled' => '1'
       });
    print "\n";
}

print "Done. \n";

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

