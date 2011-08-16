#!/usr/bin/perl -I /usr/sausalito/handlers/base/disk -I /usr/sausalito/perl
# 
# $Id: disk_restorequotas.pl,v 1.1 2001/10/31 00:13:49 bservies Exp $
#
# Copyright 2001 Sun Microsystems, Inc., All Rights Reserved
#

use Disk qw(setquota);
use CCE;

sub cce_sync_site_quotas {
	my ($cce) = @_;
	my (@oids, $obj, $ok, $old, $new);

	# Get the list of virtual site quotas from CCE
	@oids = $cce->find('Vsite');

	# Push this information out to the file system
	foreach $oid (@oids) {
		# Get the quota information for this site
	        ($ok, $obj, $old, $new) = $cce->get($oid);
		if ($ok) {
			# Sync the quota.  Ignore the errors
			setquota($cce, $obj, $oid);
		}
	}
	return 1;
}


sub cce_sync_user_quotas {
	my ($cce) = @_;
	my (@oids, $obj, $ok, $old, $new);

	# Get the list of virtual site quotas from CCE
	@oids = $cce->find('User');

	# Push this information out to the file system
	foreach $oid (@oids) {
		# Get the quota information for this site
	        ($ok, $obj, $old, $new) = $cce->get($oid);
		if ($ok) {
			# Sync the quota.  Ignore the errors
			setquota($cce, $obj, $oid);
		}
	}
	return 1;
}


#
# Main
#

my $ok;

# Open a connection to CCE
my $cce = new CCE;
$cce->connectuds();

# Fix the site quotas
$ok = cce_sync_site_quotas($cce);
if (! $ok) {
	$cce->bye('FAIL');
	exit(1);
}

# Fix the user quotas
$ok = cce_sync_user_quotas($cce);
if (! $ok) {
	$cce->bye('FAIL');
	exit(1);
}

$cce->bye('SUCCESS');
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
