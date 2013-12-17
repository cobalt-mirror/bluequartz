#!/usr/bin/perl -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email
# $Id: vacation.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use strict;

my $VacaProg = "/usr/local/sbin/vacation.pl";

use CCE;
use Email;

use Sauce::Util;
use Jcode;

my $cce = new CCE ( Namespace => "Email",
                    Domain => 'base-email' );

$cce->connectfd();

my $errors;

my($success, $user, $old) = $cce->get($cce->event_oid());

my $mail = $cce->event_object();
my $new = $cce->event_new();

if ($mail->{vacationOn} && ($mail->{vacationMsg} =~ m/^\s*$/)) {
	$cce->baddata(0, 'vacationMsg', '[[base-user.blank-vacation-msg]]');
	$cce->bye('FAIL');
	exit 1;
} 

my @pwent = getpwnam($user->{name});
my $homedir = $pwent[7];
my $forward_file = $homedir . '/.forward';

$errors += ! Sauce::Util::editblock( $forward_file, *set_on_off,
	'# VACATIONSTART', '# VACATIONEND',
	$user->{name}, $homedir, $mail->{vacationOn}, $mail->{forwardEnable} );

# set owner and permissinons
Sauce::Util::chmodfile(0644, $forward_file);
Sauce::Util::chownfile(@pwent[2,3], $forward_file);

my $msgfile = $homedir . '/.vacation_msg';
$errors += ! Sauce::Util::editfile( $msgfile,
	*set_message, $mail->{vacationMsg} );

# set owner and permissions
Sauce::Util::chmodfile(0644, $msgfile);
Sauce::Util::chownfile(@pwent[2,3], $msgfile);

# delete vacation reply db if the vacation message has changed
if ($new->{vacationMsg} || $new->{vacationOn} ) {
	&delete_vacadb($user->{name}, $homedir);
}

$cce->bye("SUCCESS");

sub delete_vacadb {
	my $username = shift;
	my $homedir = shift;

	my $vacadb = $homedir . "/.$username.db";

	if (-f $vacadb) {
		use FileHandle;

		my $fh = new FileHandle("+<$vacadb");

		# make sure there isn't a lock on the db
		# (ie vacation is replying to a message)
		my $return_buffer;
		fcntl($fh, F_SETLKW, $return_buffer);
		Sauce::Util::unlinkfile($vacadb);
		fcntl($fh, F_UNLCK, $return_buffer);

		$fh->close();
	}
}


sub set_message
{
	my $in = shift;
	my $out = shift;

	my $message = shift;

	print $out $message, "\n";

	return 1;
}
	
sub set_on_off
{
	my $in = shift;
	my $out = shift;

	my $name = shift;
	my $homedir = shift;
	my $on = shift;
	my $forward = shift;

	my $vacaLine = "";

	# check if the user is also forwarding their email, if so, let forward settings
	# deal with saving a copy of the message or not
	if (not $forward) {
		$vacaLine .= "\\$name,\t";
	}

	$vacaLine .= "\"|$VacaProg $homedir/.vacation_msg $name\"\n";

	while( <$in> ) {
		if( /\|$VacaProg/ ) {
			next;
		} else {
			print $out $_;
		}
	}

	if( $on ) {
		print $out $vacaLine;
	}

	return 1;
}

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
