#!/usr/bin/perl -I/usr/sausalito/perl -I.
#
# Author: Kevin K.M. Chiu
# Copyright(c) 2000, Cobalt Networks, Inc.
# $Id: df.pl 3 2003-07-17 15:19:15Z will $
#

use CCE;
use strict;

my $cce = new CCE;
$cce->connectfd(\*STDIN, \*STDOUT);

# df
if(!open(DF, "/bin/df |")) {
    $cce->bye("FAIL", "[[base-disk.cantExecuteDf]]");
    exit 1;
}

# read disk space
my $disk1Total = 0;
my $disk2Total = 0;
my $homeTotal = 0;
my $homeUsed = 0;
my $rootTotal = 0;
my $rootUsed = 0;
my $varTotal = 0;
my $varUsed = 0;
while(<DF>) {
    # don't need to force /home,/var,/ partitions to be on the "master" disk
    # no partition can have the same mount point anyway

    # split up line
    my @info = split(/\s+/o);
	
    # disk1Total field will be used for /dev/(h|s)da or if raided /dev/md
    # (consequently all partitions when using raid on carmel hw)
    # disk2Total field will assume any other /dev/(h|s)d(b|c|..) devices
    # this is really wrong, but schemas are not dynamic
    # There should really be a disk object as oppose to a namespace of System
    if (/\/dev\/(h|s)da/ || /\/dev\/md/) {
	# primary disk or raid
	$disk1Total += $info[1];
    }elsif (/\/dev\/(h|s)d/) {
	# other disks
	$disk2Total += $info[1];
    }

    # / partition
    if($_ =~ /\/$/o) {
        $rootTotal = $info[1];
        $rootUsed = $info[2];
    }

    # /home partition
    if($_ =~ /\/home$/o) {
        $homeTotal = $info[1];
 	$homeUsed = $info[2];
    }
   
    if($_ =~ /\/var$/o) {
	$varTotal = $info[1];
	$varUsed = $info[2];
    }
}
close DF;

# write result to CCE
my @oids = $cce->find("System");
if($#oids < 0) {
    $cce->bye("FAIL", "[[base-disk.systemObjectNotFound]]");
    exit 1;
}
my ($ok, $badKeys, @info) = $cce->set($oids[0], "Disk", {
    "disk1Total" => $disk1Total,
    "disk2Total" => $disk2Total,
    "homeTotal" => $homeTotal,
    "homeUsed" => $homeUsed,
    "rootTotal" => $rootTotal,
    "rootUsed" => $rootUsed,
    "varTotal" => $varTotal,
    "varUsed" => $varUsed
});

if(!$ok) {
    $cce->bye("FAIL", "[[base-disk.cantSetSystemObject]]");
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
