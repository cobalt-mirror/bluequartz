#!/usr/bin/perl -I. -I/usr/sausalito/perl 
#
# $Id: update-fw.pl 3 2003-07-17 15:19:15Z will $
# Author: Patrick Bose
# Copyright 2000, Cobalt Networks, Inc.
#
# This handler will update the firewall rules involved 
# with caching in the event that a change occurs with a network interface.

use CCE;
use Cache;

my $cce = new CCE(Namespace => "", Domain => "base-cache");

$cce->connectfd(\*STDIN, \*STDOUT);

my @system_oid = $cce->find("System");

if (@system_oid == 0) {
    $cce->bye("FAIL", "no_system_object");
    exit(1);
}

my ($ok, $system_obj) = $cce->get($system_oid[0], "Cache");

if (!$ok) {
    $cce->bye("FAIL", "system_object_get_failed");
    exit(1);
}

my $network = $cce->event_object();

# We only need to do something if caching is enabled
if ($system_obj->{enabled}) {
    Cache::disable_cache_fw($cce);
    Cache::enable_cache_fw($cce);
} 

$cce->bye("SUCCESS");

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
