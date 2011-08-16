#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: findalias.pl, v1.0.0.0 Mi 21 Mai 2008 15:56:35 CEST mstauber Exp $
#
# Script to generate a list of all sites, their IP and which Email Server 
# Aliasses and Web Server Aliasses they have.

use CCE;
my $cce = new CCE;

$cce->connectuds();

# Find /home in CCE
@oids = $cce->find('Vsite', '');
if ($#oids < 0) {
    print "Could not find any sites.\n";
}
else {
    $number = $#oids;
    $number++;
    print "Found $number sites.\n\n";
    foreach $result (@oids) {
        ($ok, $site) = $cce->get($result);
	print "----------------------------------------------------------------------------\n";
	print "Sitename: \t$site->{fqdn} - $site->{ipaddr}\n\n";
	@web = $cce->scalar_to_array($site->{webAliases});
	print "Web-Alias: \n";
        $web = 0;
        foreach $line (@web) {
	    $web++;	
	    print "\t\t" . $line . "\n";
	}
	if ($web == "0") { 
	    print "\t\t-- n/a --\n"; 
	}
	print "\n";
	@mail = $cce->scalar_to_array($site->{mailAliases});
	print "Mail-Alias: \n";
	$mail = 0;
	foreach $line (@mail) {
	    $mail++;
	    print "\t\t" . $line . "\n";
	}
	if ($mail == "0") { 
	    print "\t\t-- n/a --\n"; 
	}
	print "\n";
    }
    print "----------------------------------------------------------------------------\n";
}

$cce->bye();
exit 0;

