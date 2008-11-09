#!/usr/bin/perl -I/usr/sausalito/perl/ -w

use strict;

use CCE;

use Sauce::Util;

use GDBM_File;

use Data::Dumper;

# Globals.
my $PopAuth = "/etc/pop.auth";

my $cce = new CCE ( Namespace => "Email",
                    Domain => 'base-email' );

$cce->connectfd();

my %db;

if( ! tie(%db, "GDBM_File", $PopAuth, GDBM_WRITER, 0600) ) {
	$cce->warn("could_not_tie_apop $!");
	$cce->bye("FAIL");
	exit 1;
}
	


my $mail_obj = $cce->event_object();

my($success, $user_obj, $old, $new) = $cce->get($cce->event_oid());

if ( defined $new->{name} ) {
	delete $db{ $old->{name} };
}

if ( ( ! $cce->{destroyflag} ) && $mail_obj->{apop} ) {
	$db{$user_obj->{name}} = make_pw($user_obj->{password});
} else {
	delete $db{ $old->{name} };
}

untie(%db);

$cce->bye('SUCCESS');

sub make_pw
{
	my $plain = shift;
	my $crypt;

	foreach ( unpack('C*', $plain) ) {
		$crypt .= pack('C*', $_ ^= hex("ff"));
	}

	return $crypt;
}

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
