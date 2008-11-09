#!/usr/bin/perl -I. -I/usr/sausalito/perl 
#
# $Id: cache.pl 3 2003-07-17 15:19:15Z will $
# Author: Patrick Bose
# Copyright 2000, Cobalt Networks, Inc.
#
# This handler will start and stop squid, modifying the
# firewall rules to configure it as a transparent proxy.
# It will also add or remove squid from the startup scripts.
#

use CCE;
use Cache;

my $cce = new CCE(Namespace => "Cache", Domain => "base-cache");

$cce->connectfd(\*STDIN, \*STDOUT);

my $cache = $cce->event_object();

my @system_oid = $cce->find("System");

if (@system_oid == 0) {
    $cce->bye("FAIL", "no_system_object");
    exit(1);
}

if ($cache->{enabled}) {
    Cache::enable_cache($cce);
} else {
    ($ok) =$cce->set($system_oid[0], "CacheACL", {mode => "all"});
    unless($ok){
        $cce->bye(FAIL, "Unable to update Web Access Restriction Policy");
        exit(1);
    }
    Cache::disable_cache($cce);
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
