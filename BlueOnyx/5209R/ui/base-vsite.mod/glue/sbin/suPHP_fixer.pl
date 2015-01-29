#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: suPHP_fixer.pl, v1.0.0 Mon 22 Aug 2011 10:54:28 PM EDT mstauber Exp $
# Copyright 2006-2011 Team BlueOnyx. All rights reserved.
#
# This constructor handles the transition from the old suPHP to the new one, which
# allows for custom php.ini config files in the homedir of Vsites.
#
# This constructor runs through all sites and checks if suPHP is enabled.
#
# If it is, it checks if a custom php.ini is already present. If not, it will
# disable suPHP briefly and will immediately re-enabled it. That then triggers
# the creation of the custom php.ini config file in the Vsites home directory.

use CCE;
$cce = new CCE;
$cce->connectuds();

# Root check:
$id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!\n";

    $cce->bye('FAIL');
    exit(1);
}

# Find all Vsites:
@vhosts = ();
(@vhosts) = $cce->findx('Vsite');

# Start sane:
$found = "0";

# Walk through all Vsites:
for $vsite (@vhosts) {

    # Start sane:
    $custom_php_ini = "";

    ($ok, $my_vsite) = $cce->get($vsite);
    ($ok, $xvsite_php) = $cce->get($vsite, 'PHP');

    # Custom php.ini location:
    $custom_php_ini = $my_vsite->{'basedir'} . "/php.ini";

    if (($xvsite_php->{'suPHP_enabled'} == "1") &&  (!-f $custom_php_ini)) {
	if ($found == "0") {
	    print "Going through all suPHP enabled Vsites to make sure they all have a custom php.ini: \n";
	    $found++;
	}
	print "Vsite $my_vsite->{fqdn} has suPHP enabled, but is missing a custom php.ini. Fixing it!\n";
	($ok) = $cce->set($vsite, 'PHP', { 'suPHP_enabled' => '0' });
	($ok) = $cce->set($vsite, 'PHP', { 'suPHP_enabled' => '1' });
    }
}

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);
