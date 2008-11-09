#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: demand.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use CCE;
use Email;
use Sauce::Util;

my $SendmailCF = &Email::SendmailCF;

my $cce = new CCE( Namespace => 'Modem',
                      Domain => 'base-email' );

$cce->connectfd();

$obj = $cce->event_object();

my $ret = 
	Sauce::Util::editfile($SendmailCF, *set_demand, $cce,
		$obj->{connMode} eq 'demand' );
if(! $ret ) {
	$cce->warn("couldnt_write_sendmailcf");
	$cce->bye("FAIL");
} else {
	$cce->bye("SUCCESS");
}

exit(0);

sub set_demand {
	my $in = shift;
	my $out = shift;
	my $cce = shift;
	my $demand = shift;
	my $line;
	if( $demand ) {
		$line = "DialDelay=20s\n";
	} else {
		$line = "# DialDelay=20s\n";
	}
	
	while( <$in> ) {
		if( /DialDelay=/o ) {
			print $out $line;
			last;
		} else {
			print $out $_;
		}
	}

	while( <$in> ) {
		print $out $_;
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
