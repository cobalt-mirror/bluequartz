#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: meminfo.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# Author: Kevin K.M. Chiu
#

use CCE;
use strict;

my $cce = new CCE;
$cce->connectfd();

# /proc/meminfo
if(!open(MEMINFO, "</proc/meminfo")) {
    $cce->bye("FAIL", "[[base-memory.cantOpenMeminfo]]");
    exit 1;
}

# read memory information
my $memTotal = 0;
my $memFree = 0;
my $memShared = 0;
my $buffers = 0;
my $cached = 0;
my $swapTotal = 0;
my $swapFree = 0;
while(<MEMINFO>) {
    if(/^MemTotal/o) {
	my @info = split(/\s+/o);
	$memTotal = $info[1];
    }

    if(/^MemFree/o) {
	my @info = split(/\s+/o);
	$memFree = $info[1];
    }

    if(/^MemShared/o) {
	my @info = split(/\s+/o);
	$memShared = $info[1];
    }

    if(/^Buffers/o) {
	my @info = split(/\s+/o);
	$buffers = $info[1];
    }

    if(/^Cached/o) {
	my @info = split(/\s+/o);
	$cached = $info[1];
    }

    if(/^SwapTotal/o) {
	my @info = split(/\s+/o);
	$swapTotal = $info[1];
    }

    if(/^SwapFree/o) {
	my @info = split(/\s+/o);
	$swapFree = $info[1];
    }
}
close MEMINFO;

# calculate total physical memory
# the total is always less the real amount because kernel use up some memory
# this algorithm is only good if the kernel don't take up >16MB and
# memory is blocks of 16MB
my $physicalMemTotal = int (($memTotal+16384)/16384)*16;

# 4GB systems use the top 64MB as PCI & ROM address space
# pad it out by 64 to match the real DIMM total
if($physicalMemTotal >= 4032) {
    $physicalMemTotal = 4096;
}

# write result to CCE
my @oids = $cce->find("System");
if($#oids < 0) {
    $cce->bye("FAIL", "[[base-memory.systemObjectNotFound]]");
    exit 1;
}
my ($ok, $badKeys, @info) = $cce->set($oids[0], "Memory", {
    "physicalMemTotal" => $physicalMemTotal,
    "memTotal" => $memTotal,
    "memFree" => $memFree,
    "memShared" => $memShared,
    "buffers" => $buffers,
    "cached" => $cached,
    "swapTotal" => $swapTotal,
    "swapFree" => $swapFree
});

if(!$ok) {
    $cce->bye("FAIL", "[[base-memory.cantSetSystemObject]]");
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);
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
