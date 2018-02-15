#!/usr/bin/perl

use strict;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/raid);
use warnings;
use SMART;
use CCE;
use Base::Disk qw(disk_getpartitions);

my $DEBUG = 0;

my $cce = new CCE;
$cce->connectfd();

# find which disks to use
my $parthash = disk_getpartitions();
use Data::Dumper;
$DEBUG && print STDERR Dumper($parthash);

my @physicaldrives = ();

foreach my $drive (keys %$parthash) {
    if ($drive =~ /(hd[a-z])$/) {
	push @physicaldrives, $1;
    }
}


$DEBUG && print STDERR Dumper (@physicaldrives);

my $smart_info = get_smart_info(@physicaldrives);

$DEBUG && print STDERR Dumper($smart_info);

my @array = smart_to_array($smart_info);

$DEBUG && print STDERR Dumper(@array);

my $oid = $cce->event_oid();

my ($ok) = $cce->set($oid, 'SMART', { 'snapshot' => $cce->array_to_scalar(@array)});
if (!$ok) {
    $cce->warn('[[base-raid.noSmartSnapshot]]');
    $cce->bye('FAIL');
    exit 1;
}

$cce->bye('SUCCESS');
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
