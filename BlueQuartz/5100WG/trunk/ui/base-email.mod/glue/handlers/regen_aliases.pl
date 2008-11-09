#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: regen_aliases.pl 3 2003-07-17 15:19:15Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# rewrites every User.name and User.Email.aliases entry that appears
# in /etc/mail/virtusertable only when System.[hostname|domainname] changes.
#
my $DEBUG = 0;

use CCE; 
use Sauce::Util; 
use Email;
use strict;

if ($DEBUG) {
	use Data::Dumper;
	open(STDERR, ">/tmp/aliaseslog");
}

my $VIRTFILE = &Email::Virtuser();

my $cce = new CCE('Domain' => 'base-email');
$cce->connectfd();

my $obj = $cce->event_object();
my $new = $cce->event_new();
my $old = $cce->event_old();
my $uoid = $cce->event_oid();


# if the system.hostname or system.domainname get changed,
# we need to regenerate every record.  suck.
my $old_fqdn = $old->{'hostname'} . '.' . $old->{'domainname'};
my $new_fqdn = $obj->{'hostname'} . '.' . $obj->{'domainname'};
my($status,$cceok,$myoid,@user_oids,$user_obj,$email_obj);
@user_oids = $cce->find("User");

foreach $myoid (@user_oids) {
	($cceok,$user_obj) = $cce->get($myoid);
	if (!$cceok) {
		print STDERR "Error: couldn't get user object $myoid\n";
		$cce->bye("FAIL");
		exit(1);
	}
	# construct user@hostname.domainname.com
	my $newemail = $user_obj->{'name'} . '@' . $new_fqdn;
	my $oldemail = $user_obj->{'name'} . '@' . $old_fqdn;

	# add the "user:  user" mapping entry from /etc/mail/virtusertable
	# must be done in two editfile() operations so you ensure
	# that the user mapping gets in before the aliases.
	$status = Sauce::Util::editfile(
				'/etc/mail/virtusertable',
				\&Sauce::Util::hash_edit_function,
				'#',
				{ 're' => '\s*', 'val' => "\t" }, 
				{
				  $newemail => $user_obj->{'name'},
				  $oldemail => '',
				},
	);

	if (!$status || ($status eq 'FAIL')) {
		print STDERR "Warning: adding user for $myoid failed.\n";
	}

	# get the users email namespace for their aliases
	($cceok,$email_obj) = $cce->get($myoid,"Email");
	if (!$cceok) {
		print STDERR "Error: couldn't get user Email object: $myoid\n";
		$cce->bye("FAIL");
		exit(1);
	}

	my @aliasList = $cce->scalar_to_array($email_obj->{'aliases'});
	my %hash = map	{
			  $_ . '@' . $new_fqdn => $user_obj->{'name'},
			  $_ . '@' . $old_fqdn => '',
			} @aliasList;

	$DEBUG && print STDERR Dumper(\%hash);

	$status = Sauce::Util::editfile(
				'/etc/mail/virtusertable',
				\&Sauce::Util::hash_edit_function,
				'#',
				{ 're' => '\s*', 'val' => "\t" }, 
				\%hash,
	);
}
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
