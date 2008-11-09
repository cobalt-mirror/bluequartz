#!/usr/bin/perl 
# $Id: apop_pw.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# This handler maintains a version of the user's password
# for use in the APOP database. If we don't maintain this, you
# would need to have the user's password any time you wanted to 
# enable APOP for that person (user mod, vsite apop re-enable).
#
# Plus, even if you know the password, things get messy when you
# cannot set properties in different namespaces at the same time.
# You would have to allow a set to the apop property to succeed
# and then hope that the password gets set next to actually make
# the change.

my $cce = new CCE;
$cce->connectfd();

my $user_obj = $cce->event_object();

$pw = make_pw($user_obj->{password});
my($ok) = $cce->set( $cce->event_oid(), 'APOP', { 'apop_password' => $pw } );

if ( !$ok ) {
	$cce->bye('FAIL');
	exit 1;
}

$cce->bye('SUCCESS');

# creates a password suitable for the APOP database
# params: plaintext password
# returns: apop password; '.= chr(0) . chr(0)' to the result 
#          before adding to db
sub make_pw
{
	my $plain = shift;
	my $crypt;

	foreach ( unpack('C*', $plain) ) {
		$crypt .= pack('C*', $_ ^= hex('ff'));
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
