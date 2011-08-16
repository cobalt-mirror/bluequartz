#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email/
# $Id: local_hosts.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use strict;
use CCE;
use Email;
use Sauce::Util;

my $Sendmail_cw = Email::SendmailCW;

my $cce = new CCE( Domain => 'base-email' );

$cce->connectfd();

my $sys_obj = $cce->event_object();
my $new_sys = $cce->event_new();
my ($ok, $email) = $cce->get($cce->event_oid(), 'Email');

if (not $ok) {
	$cce->bye('FAIL');
	exit(1);
}

if ($new_sys->{hostname} || $new_sys->{domainname} || 
	($cce->event_property() eq 'acceptFor')) {
	if(!Sauce::Util::replaceblock($Sendmail_cw,
		'# Cobalt System Section Begin',
		&make_sendmail_cw($email, $sys_obj),
		'# Cobalt System Section End')
		) {
		$cce->warn('[[base-email.cantEditFile]]', 
				{ 'file' => Email::SendmailCW });
		$cce->bye('FAIL');
		exit(1);
	}
}

$cce->bye('SUCCESS');
exit(0);

sub make_sendmail_cw
{
	my $obj = shift;
	my $sys = shift;

	my @aliases;

	@aliases = $cce->scalar_to_array($obj->{acceptFor});
	
	# always accept email addressed to this machine
	# there is a flag for this in cce, but no ui widget
	# don't know why you would not want to accept mail to your fqdn
	push @aliases, $sys->{hostname} . "." . $sys->{domainname};

	return join("\n",@aliases);
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
