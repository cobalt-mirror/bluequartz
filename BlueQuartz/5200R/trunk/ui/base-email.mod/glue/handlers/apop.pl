#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: apop.pl 620 2005-10-28 09:31:05Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# This handler maintains the user entries in the apop database.
# It handles user create/destroy, apop enable, and password changes.

use CCE;
use GDBM_File;

# Globals.
my $PopAuth = '/etc/pop.auth';

my $cce = new CCE ( Namespace => 'APOP',
                    Domain => 'base-email' );
$cce->connectfd();

# make sure pop auth db exists and is properly owned
if (! -f $PopAuth && -x '/usr/sbin/popauth' ) {
	system('/usr/sbin/popauth -init');
	if ($?) {
		$cce->bye('FAIL');
		exit(1);
	}
}

my($success, $apop_obj, $oldm, $newm) = $cce->get($cce->event_oid(), "APOP");
($success, my $user_obj, my $old, my $new) = $cce->get($cce->event_oid());

# if this is a destroy and the user didn't have APOP enabled, just say goodbye
if ($cce->event_is_destroy() && !$oldm->{enabled}) {
	$cce->bye('SUCCESS');
	exit(0);
}

# password is required for enabled user
if ($apop_obj->{enabled} && !$apop_obj->{apop_password}) {
	$cce->warn('[[base-email.apopPwRequired]]');
	$cce->bye('FAIL');
	exit 1;
}

my %db;
Sauce::Util::modifyfile($PopAuth);
if( ! tie(%db, 'GDBM_File', $PopAuth, GDBM_WRITER, 0600) ) {
	$cce->warn('could_not_tie_apop $!');
	$cce->bye('FAIL');
	exit 1;
}

# check if apop is enabled for the vsite
my $vsite_ok = 1;
if ( $user_obj->{site} ) {
	($vsoid) = $cce->find("Vsite", { "name" => $user_obj->{site} });
	my($success, $vsite_obj) = $cce->get($vsoid, "APOP");
	$vsite_ok = 0 if ( !$vsite_obj->{enabled} ) 
}

# delete old entry if name changed
if ( defined $new->{name} ) {
	delete $db{ $old->{name} . chr(0) };
}

if ( ( ! $cce->{destroyflag} ) && $vsite_ok && $apop_obj->{enabled} ) {
	$db{ $user_obj->{name} . chr(0) } = $apop_obj->{apop_password} . chr(0) . chr(0);
} else {
	delete $db{ $old->{name} . chr(0) };
}

untie(%db);

# Fail if try to enable for user when not allowed for site
if ( $newm->{enabled} && !$vsite_ok ) {
	$cce->bye('FAIL');
	exit 1;
}	

$cce->bye('SUCCESS');
exit 0;

1;
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
