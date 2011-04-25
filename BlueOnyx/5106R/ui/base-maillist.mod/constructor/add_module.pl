#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: add_module.pl,v 1.0.0.0 Tue 26 Apr 2011 12:17:28 AM CEST mstauber Exp $
# Copyright 2011, Team BlueOnyx. All rights reserved.
#
# Constructor to add Majordomo intergation info to "Installed Software List".
# Makes it easier to spot which kind of list this server runs.

use CCE;
my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectuds();

# Get base-mailman-capstone version:
$version = `/bin/rpm -q base-maillist-capstone --qf %{version}`;
$product = "Majordomo";
$conflict_product = "MailMan";

# Make sure we really got a version number back:
unless ($version =~ /^(\d+\.)?(\d+\.)?(\*|\d+)$/) {
	print "$product not installed!\n";
	$cce->bye();
	exit 0;
}

# Find if the conflicting Package was is installed and is still listed:
@oids = $cce->find('Package', {'name' => $conflict_product});
if ($#oids < 0) {
    # Object not found in CCE
}
else {
    # Find the conflicting Package and destroy the object:
    ($sys_oid) = $cce->find('Package', {'name' => $conflict_product});
    $cce->destroy($sys_oid);
}    

# Create the Package info on first time run:
my @oids = $cce->find('Package', {'name' => $product });
if ($#oids < 0) {
    $cce->create('Package', { 'name' => $product,
                              'version' => "v$version",
                              'vendor' => 'Project_BlueOnyx',
                              'nameTag' => $product,
                              'vendorTag' => 'Project BlueOnyx',
                              'shortDesc' => "$product mailing list integration.",
                              'new' => 0, 'installState' => 'Installed',
                              'RPMList' => ""
                          });
}

# Update Package info on later runs:
@oids = $cce->find('Package', {'name' => $product});
if ($#oids < 0) {
    # Object not found in CCE
}
else {
    # Find the system object and set the version number:
    ($sys_oid) = $cce->find('Package', {'name' => $product});
    ($ok, $sys) = $cce->get($sys_oid);
    ($ok) = $cce->set($sys_oid, '',{
    'version' => "v$version"
    });
}

$cce->bye();
exit 0;

