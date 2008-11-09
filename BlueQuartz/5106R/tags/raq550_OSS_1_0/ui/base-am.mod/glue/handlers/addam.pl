#!/usr/bin/perl
# $Id: addam.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# 
# ActiveMonitor._CREATE handler
# author: Tim Hockin (based on work by Jonathan Mayer <jmayer@cobalt.com>)
#
# generates the following i18n messages:
#		alreadyMadeAM

use strict;
use Sauce::Util;
use FileHandle;
use CCE;

my $cce = new CCE;
$cce->connectfd();

# retreive user object data:
my $oid = $cce->event_oid();
my $amobj = $cce->event_object();

my $enabled = $amobj->{enabled} ? 'true' : 'false';
my $maillist = $amobj->{alertEmailList} || '';
my @mla = $cce->scalar_to_array($maillist);
$maillist = join(', ', @mla);

##########################################################################
# error checking
##########################################################################

# See if an AM obj exists
my (@amoid) = $cce->find('ActiveMonitor');

if ($#amoid >= 1) {
    $cce->warn('[[base-am.alreadyMadeAM]]');
    $cce->bye('FAIL');
    exit 1;
}


##########################################################################
# Add the AM config
##########################################################################

my $fun = sub {
    my ($fin, $fout) = (shift, shift);
    print $fout "enabled = $enabled\n";
    print $fout "lock_file = /var/lock/swatch.lock\n";
    print $fout "email_list = $maillist\n";
    return 1;
}; 

my $ok = Sauce::Util::editfile("/etc/swatch.conf", $fun);
if ($ok) {
	$cce->bye('SUCCESS');
	exit(0);
} else {
	$cce->bye('FAIL');
	exit(1);
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
