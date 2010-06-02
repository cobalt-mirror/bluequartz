#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email/
# $Id: mapmaker.pl 489 2005-08-09 14:00:26Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

# Author: Harris Vaegan-Lloyd
# Make maps of all the text files in /etc/mail/

use strict;

use CCE;
use Email;
use Sauce::Util;

my $cce = new CCE( Domain => 'base-email' );

$cce->connectfd();

my $obj = $cce->event_object();

if ($cce->event_is_destroy())
{
    $obj = $cce->event_old();
}

# check for explicit property saying whether to rebuild
# the maps
if (exists($obj->{build_maps}) && !$obj->{build_maps})
{
    # explicit property saying to not build maps
    my ($ok) = $cce->set($cce->event_oid(), $cce->event_namespace(), { 'build_maps' => 1 });
    $cce->bye('SUCCESS');
    exit(0);
}

# Hacky virtusertable auto-sort
# Sauce::Util::editfile('/etc/mail/virtusertable', *sortvirtuser); 

foreach my $file (($Email::ACCESS, $Email::VIRTUSER)) {
    if( ! makemap($file) ) {
		$cce->warn("couldnt_make_map", { file => $file } );
		$cce->bye("FAIL");
		exit 1;
	}
}

$cce->bye("SUCCESS");

exit 0;

sub makemap
{
	my $map = shift;

	# Yes, I know this could have been done in a shorter manner, but handling
	# shell return values in perl always makes me do a double take so I
	# took the clearest path, ratehr than the shortest.

        if( system("/usr/sbin/postmap hash:$map >/dev/null 2>&1") == 0 ) { 
		return 1;
	} else {
		return 0;
	}
}

sub sortvirtuser
{
	my($in, $out) = @_;

	my ($routes, $ca, $users);
	while(<$in>) {
		if(/^\@/ && /\%/) {
			$routes .= $_;
		} elsif (/^@/) {
			$ca .= $_; 
		} elsif (/\@/) {
			$users .= $_;
		} elsif (/(catch-all email|accept-email-at)/) {
			next;
		} else {
			print $out $_;
		}
	}

	print $out <<EOF;
# accept-email-at-domain routes
$routes# end accept-email-at-domain routes
$users# catch-all email handlers
$ca# end catch-all email handlers
EOF

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
