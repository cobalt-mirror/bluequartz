#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: addRAIDPkg.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#

use CCE;
use Cobalt::RAID;
my $cce = new CCE;

$cce->connectuds();

my $packageName = 'RAID'; 
my $isConfigurable = 0;
my $add_raid_pkg = 1;
my ($system, $ok, $prod, $level);

my @oids = $cce->find('System', {});
if (@oids == 1) {
	($ok, $system) = $cce->get($oids[0]);
	if ( $ok ) {
   	$prod = ${$system}{'productBuild'};
		# raid level is configurable on Raqs
		if ($prod  =~ /\d+R/) {
			$isConfigurable = 1;
			$add_raid_pkg = 0;
		} else {
			# we ship RAID1 on nonconfigurable systems
			$level="1";
		}
	}
} 

my $disks = Cobalt::RAID::raid_get_numdisk();
$isConfigurable = 0 if ( $disks < 2 );

if (@oids == 1) {
	# The level can only be set once (reconfiguration is not allowed)
	# therefore we don't set a level now for configurable systems
	if ($level) {
		$ok = $cce->set($oids[0], 'RAID', { level => $level, configurable => $isConfigurable, disks => $disks }); 
	} else {
		$ok = $cce->set($oids[0], 'RAID', { configurable => $isConfigurable, disks => $disks }); 
	}
}

@oids = $cce->find('Package', {'name' => $packageName, vendor => 'Cobalt' });
if (($#oids < 0) && $add_raid_pkg) {
  $cce->create('Package', { 'name' => $packageName,
			    'vendor' => 'Cobalt',
		  	    'version' => 'v1.1',
			    'nameTag' => '[[base-raid.pkgName]]',
			    'vendorTag' => '[[base-raid.pkgVendor]]',
			    'shortDesc' => '[[base-raid.pkgDescription]]',
			    'new' => 0,
			    'installState' => 'Installed' });
}
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
