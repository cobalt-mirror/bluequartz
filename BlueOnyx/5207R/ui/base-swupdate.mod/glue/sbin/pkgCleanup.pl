#!/usr/bin/perl -I/usr/sausalito/perl
use CCE;
$cce = new CCE;
$cce->connectuds();
(@PKGS) = $cce->findx('Package', '', { "installState" => "Available" });
for $pkg (@PKGS) {
    ($ok, $my_vsite) = $cce->destroy($pkg);
}
$cce->bye('SUCCESS');
exit(0);
