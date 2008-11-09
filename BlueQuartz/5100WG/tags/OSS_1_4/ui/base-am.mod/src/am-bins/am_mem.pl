#!/usr/bin/perl -I/usr/sausalito/perl
# test the memory state
# go yellow if any three of these are true:
#       Real Mem >= memYellow %
#       Real Mem Free < minYellow MB
#       Swap Mem >= swapYellow %
#       Pages/sec >= pagesYellow
#       Swaps/sec >= swapsYellow
# go red if any three of these are true: 
#       Real Mem >= memRed % 
#       Real Mem Free < minRed MB
#       Swap Mem >= swapRed %
#       Pages/sec >= pagesRed
#       Swaps/sec >= swapsRed
# Tim Hockin

use AM::Util;
use strict;

my %am_states = am_get_statecodes();
my ($memYellow, $memRed, $swapYellow, $swapRed);
my ($minYellow, $minRed);
my ($pagesYellow, $pagesRed, $swapsYellow, $swapsRed);
my $errcount;
my $debug = 0;

sub dprint
{
	if ($debug) {
		print @_;
	}
}

# init threshholds
if (!$ENV{memYellow}) {
	$ENV{memYellow} = 90;
}
if (!$ENV{memRed}) {
	$ENV{memRed} = 95;
}
if (!$ENV{minYellow}) {
	$ENV{minYellow} = 3.0;
}
if (!$ENV{minRed}) {
	$ENV{minRed} = 1.5;
}
if (!$ENV{swapYellow}) {
	$ENV{swapYellow} = 35;
}
if (!$ENV{swapRed}) {
	$ENV{swapRed} = 50;
}
if (!$ENV{pagesYellow}) {
	$ENV{pagesYellow} = 400;
}
if (!$ENV{pagesRed}) {
	$ENV{pagesRed} = 1000;
}
if (!$ENV{swapsYellow}) {
	$ENV{swapsYellow} = 200;
}
if (!$ENV{swapsRed}) {
	$ENV{swapsRed} = 500;
}

# get memory numbers
open(MEM, "</proc/meminfo");

# read header
<MEM>;

# read mem line
$_ = <MEM>;
my @minfo = split(/\s+/);

# read swap line
$_ = <MEM>;
my @sinfo = split(/\s+/);

close(MEM);

# check pages per second
my ($nm, $pin, $pout, $swin, $swout, $uptime);

open(STAT, "</proc/stat");
while (<STAT>) {
	if (/^page/) {
		($nm, $pin, $pout) = split(/\s+/);
	} elsif (/^swap/) {
		($nm, $swin, $swout) = split(/\s+/);
	}
}

close(STAT);

open(UP, "</proc/uptime");
$_ = <UP>;
($uptime, $nm) = split(/\s+/);

# Check for Red status
$errcount = 0;

# used = used - buffers
my $used = $minfo[2] - $minfo[5];
if (($used * 100) / $minfo[1] >= $ENV{memRed}) {
	dprint("Red1: ". ($used * 100) / $minfo[1] ." >= $ENV{memRed}\n");
	$errcount++;
}
# minimum free memory (in MB) (free + buffers)
my $free = $minfo[3] + $minfo[5];
if ($free / (1024*1024) < $ENV{minRed}) {
	dprint("Red2: ". $free / (1024*1024) ." < $ENV{minRed}\n");
	$errcount++;
}
# used swap
if (($sinfo[2] * 100) / $sinfo[1] >= $ENV{swapRed}) {
	dprint("Red3: ". ($sinfo[2] * 100) / $sinfo[1] ." >= $ENV{swapRed}\n");
	$errcount++;
}
# how many pages per sec avg?
if ((($pin + $pout) / $uptime) >= $ENV{pagesRed}) {
	dprint("Red4: ". ($pin + $pout) / $uptime ." >= $ENV{pagesRed}\n");
	$errcount++;
}
# how many swaps per sec avg?
if ((($swin + $swout) / $uptime) >= $ENV{swapsRed}) {
	dprint("Red5: ". ($swin + $swout) / $uptime ." >= $ENV{swapsRed}\n");
	$errcount++;
}
if ($errcount >= 3) {
	print("[[base-am.amMemWarning,status=\"[[base-am.amUseHeavy]]\"]]");
	exit $am_states{AM_STATE_RED};
}

# Check for Yellow status
$errcount = 0;

if (($used * 100) / $minfo[1] >= $ENV{memYellow}) {
	dprint("Yellow1: " . ($used * 100) / $minfo[1] 
		. " >= $ENV{memYellow}\n");
	$errcount++;
}
if ($free / (1024*1024) < $ENV{minYellow}) {
	dprint("Yellow2: ". $free / (1024*1024) ." < $ENV{minYellow}\n");
	$errcount++;
}
if (($sinfo[2] * 100) / $sinfo[1] >= $ENV{swapYellow}) {
	dprint("Yellow3: " . ($sinfo[2] * 100) / $sinfo[1] 
		. " >= $ENV{swapYellow}\n");
	$errcount++;
}
if ((($pin + $pout) / $uptime) >= $ENV{pagesYellow}) {
	dprint("Yellow4: " . ($pin + $pout) / $uptime 
		. " >= $ENV{pagesYellow}\n");
	$errcount++;
}
if ((($swin + $swout) / $uptime) >= $ENV{swapsYellow}) {
	dprint("Yellow5: ". ($swin + $swout) / $uptime 
		. " >= $ENV{swapsYellow}\n");
	$errcount++;
}
if ($errcount >= 3) {
	print("[[base-am.amMemWarning,status=\"[[base-am.amUseModerate]]\"]]");
	exit $am_states{AM_STATE_YELLOW};
}

print("[[base-am.amMemWarning,status=\"[[base-am.amUseLight]]\"]]");
exit $am_states{AM_STATE_GREEN};
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
