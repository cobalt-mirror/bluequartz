#!/usr/bin/perl -I/usr/sausalito/perl/ -w
# $Id: forward.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use strict;

use CCE;

use Sauce::Config;
use Sauce::Util;

my $cce = new CCE ( Namespace => "Email",
                    Domain => 'base-email' );

$cce->connectfd();

my $errors;

my($success, $user, $old, $new) = $cce->get($cce->event_oid());

my $mail = $cce->event_object();

my @pwent = getpwnam($user->{name});
my $forward_file = $pwent[7] . '/.forward';

my $data = "";
my $bad_local_users = "";

if (! $mail->{forwardEnable}) {
	$data .= "# forwarding not enabled\n";
} else {
	for my $forward (CCE->scalar_to_array($mail->{forwardEmail})) {
		if(&valid_forward($forward)) {
			$data .= "$forward\n";
		} else {
			$bad_local_users ? ($bad_local_users .=	", $forward") : ($bad_local_users .= $forward);
		}	
	}
}
if ($mail->{forwardEnable} && $mail->{forwardSave}) {
	$data .= "\\" . $user->{name};
}

Sauce::Util::replaceblock( $forward_file,
	'# forward.pl: Do not edit below this line',
	$data,
	'# forward.pl: Do not edit above this line',
	0644);

# .forward needs to be 0644 or sendmail ignores it
Sauce::Util::chmodfile(0644, $forward_file);
Sauce::Util::chownfile(@pwent[2,3], $forward_file);

if ($bad_local_users) {
	$cce->warn("nonExistentUser", { 'users' => "$bad_local_users" });
	$cce->bye("FAIL");
	exit(1);
}

$cce->bye("SUCCESS");
exit(0);

sub valid_forward {
	my $forward = shift;

	# check for fully qualified email address
	if ($forward =~ /^[\w\-\_\.]+\@[\w\-\_\.]+$/) {
		return 1;
	}

	# check if a the forward is a valid local user name
	if (not getpwnam($forward)) {
		return 0;
	}

	return 1;
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
