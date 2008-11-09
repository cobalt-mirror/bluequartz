#!/usr/bin/perl -I/usr/sausalito/perl
#
# Copyright (c) 2000 Cobalt Networks

use CCE;
my $cce = new CCE;

$cce->connectuds();
my @oids = $cce->find('Package', {'name' => 'OS' });
if ($#oids < 0) {
    my (%rpms, $rpmlist);

    # snarf in package list. avoid duplicates
    if (open(CONF, '/var/lib/cobalt')) {
	while (<CONF>) {
		next if /^\s*#/;
		next unless /\S/;
		next if /^\S+:/;
		$rpms{"$1-$2"} = 1 if /^(\S+)\s+(\S+)/;
	}
	close(CONF);
    }

    $rpmlist = $cce->array_to_scalar(keys %rpms);
    $cce->create('Package', { 'name' => 'OS',
			      'version' => 'v6.0',
			      'vendor' => 'Cobalt',
			      'nameTag' => '[[base-carmel.osName]]',
			      'vendorTag' => '[[base-carmel.osVendor]]',
			      'shortDesc' => '[[base-carmel.osDescription]]',
			      'new' => '', 'installState' => 'Installed',
			      'RPMList' => $rpmlist
			  });
    my ($sysoid) = $cce->find('System');
    $cce->set($sysoid, 'SWUpdate', { 'rpmsInstalled' => 
				     $cce->array_to_scalar(%rpms) });
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
