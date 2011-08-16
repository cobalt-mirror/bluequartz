#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
#
# $Id: fix_web-and-email-server-aliasses.pl, v 1.0.0.1 Oct 15 2009 05:32:01 AM EDT mstauber Exp $
# Copyright 2006-2009 Team BlueOnyx. All rights reserved.
#
# Sometimes the "Email Server Aliasses" and "Web Server Aliasses" show correctly in the GUI, but are
# not present in the Sendmail and Apache config files. This typically happens after migrations.
#
# This script walks through all sites and will briefly remove the aliasses, will save the changes
# and will then set the same aliasses again. This effectively forces the config files to be 
# rewritten.
#
# Afterwards the "Email Server Aliasses" and "Web Server Aliasses" will work again.
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

print "Going through all sites to set Web Server and Email Server aliasses: \n";

# Walk through all Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

    print "Processing Site: $my_vsite->{fqdn} \n";

    ($ok) = $cce->set($vsite, '',{
        'webAliases' => ''
       });
    ($ok) = $cce->set($vsite, '',{
        'webAliases' => $my_vsite->{webAliases}
       });

    ($ok) = $cce->set($vsite, '',{
        'mailAliases' => ''
       });
    ($ok) = $cce->set($vsite, '',{
        'mailAliases' => $my_vsite->{mailAliases}
       });
}

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

