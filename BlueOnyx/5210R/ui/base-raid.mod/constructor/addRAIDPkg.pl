#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: addRAIDPkg.pl
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#

use CCE;
use Cobalt::RAID;
my $cce = new CCE;

$cce->connectuds();

my $packageName = 'RAID'; 
my $isConfigurable = 0;
my ($system, $ok, $prod, $level);

my @oids = $cce->find('System', {});
if (@oids == 1) {
    ($ok, $system) = $cce->get($oids[0]);
    if ( $ok ) {
		$prod = ${$system}{'productBuild'};
	    
	    # raid level is not configurable on Bluapp
	    my $home_partition = `/bin/df -l -P /home | grep "/home"`;
	    $home_partition = (split / +/, $home_partition)[0];
	    if ( $home_partition =~ /VolGroup00/ ) {
		my $lvm = 1;
		my $volgroup_name = "VolGroup00";
		my $home_device = `/usr/sbin/vgdisplay $volgroup_name --verbose | /bin/grep 'PV Name'`;
		($home_device) = (split / +/, $home_device)[3];
		if( $home_device =~ /\/dev\/md/ ) {
		    $raid = 1;
		    $level = `/sbin/mdadm --misc -D $home_device |grep 'Raid Level'`;
		    ($level) = (split /raid/, $level)[1];
		} else {
		    $raid = 0;
		    $level = 0;
		}
	    } elsif ($home_partition =~ /\/dev\/md/ ) {
			$home_device = $home_partition;
			$lvm = 0;
			$raid = 1;
			$level = `/sbin/mdadm --misc -D $home_device |grep 'Raid Level'`;
			($level) = (split /raid/, $level)[1];
	    } else {
			$lvm = 0;
			$raid = 0;
			$level = 0;
	    }
	}
}

my $add_raid_pkg = 1 if ($raid);


my $disks = Cobalt::RAID::raid_get_numdisk();

if ( $disks !~ /^\d+$/ ) {
	$disks=0;
}

if (@oids == 1) {
    # The level can only be set once (reconfiguration is not allowed)
    # therefore we don't set a level now for configurable systems
    if ($level) {
	$ok = $cce->update($oids[0], 'RAID', { level => $level, configurable => $isConfigurable, disks => $disks }); 
	} else {
	    $ok = $cce->update($oids[0], 'RAID', { configurable => $isConfigurable, disks => $disks }); 
	}
}

#@oids = $cce->find('Package', {'name' => $packageName, vendor => 'Cobalt' });
#if (($#oids < 0) && $add_raid_pkg) {
#    $cce->create('Package', { 'name' => $packageName,
#			      'vendor' => 'Cobalt',
#			      'version' => 'v1.1',
#			      'nameTag' => '[[base-raid.pkgName]]',
#			      'vendorTag' => '[[base-raid.pkgVendor]]',
#			      'shortDesc' => '[[base-raid.pkgDescription]]',
#			      'new' => 0,
#			      'installState' => 'Installed' });
#}
$cce->bye();
exit 0;
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
