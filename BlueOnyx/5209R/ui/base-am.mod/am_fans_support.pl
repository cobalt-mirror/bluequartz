#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: am_fans_support.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use strict;
use CCE;

my ($cce, @oids);
my $oid = 0;
my $success = 0;
my $ok;

$cce = new CCE;
$cce->connectuds();

# check if AM exists already
@oids = $cce->find("ActiveMonitor");
if ($#oids >= 0) {
    $oid = $oids[0];
    
    # if file exists, check for support by reading it
    # if it doesn't exist, we have no support
    if ( -e "/proc/cobalt/faninfo" ) {
	my $faninfo = `cat /proc/cobalt/faninfo`;
	if ($faninfo =~ /not supported/) {
	    ($ok) = $cce->set($oid, 'Fans', { 'hideUI' => 1, 'enabled' => 0});
	    if (not $ok) {
		print STDERR "Constructor for Fans failed when hiding amFans\n";
		$cce->bye();
		exit 1;
	    }
	} else {
	    ($ok) = $cce->set($oid, 'Fans', { 'hideUI' => 0, 'enabled' => 1});
	    if (not $ok) {
		print STDERR "Constructor for Fans failed when enabling fans\n";
		$cce->bye();
		exit 1;
	    }
	}
    } else {
	($ok) = $cce->set($oid, 'Fans', { 'hideUI' => 1, 'enabled' => 0});
	if (not $ok) {
	    print STDERR "Constructor for Fans failed when hiding amFans\n";
	    $cce->bye();
	    exit 1;
	}
    }
}

$cce->bye();

exit 0;
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
