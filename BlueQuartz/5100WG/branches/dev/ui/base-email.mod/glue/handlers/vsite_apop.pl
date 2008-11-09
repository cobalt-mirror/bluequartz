#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: vsite_apop.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2002 Sun Microsystems, Inc., All rights reserved.
#
# This handler maintains the user entries in the apop database.
# It handles disabling/enabling apop at the site level.
#

use CCE;
use GDBM_File;

# Globals.
my $PopAuth = '/etc/pop.auth';

my $cce = new CCE(Domain => 'base-email');
$cce->connectfd();

# make sure pop auth db exists and is properly owned
if (! -f $PopAuth) {
	system('/usr/sbin/popauth -init');
	if ($?) {
		$cce->bye('FAIL');
		exit(1);
	}
}

my $vsite = $cce->event_object();
my ($ok, $apop) = $cce->get($cce->event_oid(), 'APOP');

if (!$ok) {
	$cce->warn('[[base-email.cantReadAPOP]]');
	$cce->bye('FAIL');
	exit(1);
}

# see if there are any users using apop
my @apop_uoids = $cce->find('User',
			    { 'site' => $vsite->{name}, 'APOP.enabled' => 1 });
if (scalar(@apop_uoids) == 0) {
	# no users with apop enabled so just leave successfully
	$cce->bye('SUCCESS');
	exit(0);
}

#
# otherwise need to sync the database of pop users so that users for this
# site with apop enabled get added/removed when apop is enabled/disabled
# for the site
#
my %db;
Sauce::Util::modifyfile($PopAuth);
if(!tie(%db, 'GDBM_File', $PopAuth, GDBM_WRITER, 0600)) {
	$cce->warn('could_not_tie_apop $!');
	$cce->bye('FAIL');
	exit 1;
}

# iterate all users with APOP enabled
for my $uoid (@apop_uoids) {
	($ok, my $user) = $cce->get($uoid);
	($ok, my $user_apop) = $cce->get($uoid, 'APOP');
	if (!$ok) {
		$cce->bye('FAIL', 'cantGetUser');
		exit(1);
	}

	if ($apop->{enabled}) {
		# add user to apop db
		$db{ $user->{name} . chr(0) } = $user_apop->{apop_password} .
							chr(0) . chr(0);
	} else {
		# remove user from apop db
		delete $db{ $user->{name} . chr(0) };
	}
}

untie(%db);
$cce->bye('SUCCESS');
exit(0);
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
