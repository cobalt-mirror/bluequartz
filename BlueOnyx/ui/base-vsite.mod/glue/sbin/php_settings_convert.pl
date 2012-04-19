#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
#
# $Id: php_settings_convert.pl, v 1.0.0 Thu 19 Apr 2012 08:38:09 AM CEST mstauber Exp $
# Copyright 2006-2012 Team BlueOnyx. All rights reserved.
#
# This script walks through all sites that have PHP or suPHP enabled. It just forces
# CCE to update the existing PHP settings in the Apache Vhost container of these 
# Vsites and also in the separate php.ini files of suPHP enabled sites.
#
# This script is useful when the defaul PHP settings of BlueOnyx change and we need 
# to make sure that the new settings are pushed out to all existing Vsites that 
# have PHP enabled.
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
my (@vhosts) = $cce->findx('Vsite');

print "Going through all sites to activate new PHP features: \n";

# Walk through all Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

    print "Processing Site: $my_vsite->{fqdn} \n";

    ($ok) = $cce->set($vsite, 'PHPVsite',{
        'force_update' => time()
       });

}

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

