#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: am_ecc.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# test the ECC state
# this is a complicated test, so let me write the spec here
# This test checks /var/log/kernel for NMI messages since the 
# last reboot, or within the last day, whichever is less.
#
# All messages start with "NMI:"
# Messages may be of 3 forms:
# 1. One or more of the following
#    (PCI tx data error) 
#    (PCI rx data error) 
#    (PCI address error) 
#    (DRAM uncorrectable error) 
#    (DRAM correctable error)
#    (Shutdown cycle detected)
#    Optionally followed by DRAM addresses and rows
# 2. "last error repeated X times"
# 3. "X messages were throttled" which means that there were too 
#     many NMI's to be printed
#
# We return in the following ways, in bad to good order
# 1. ANY uncorrectable errors (RED)
# 2. Throttled NMIs, which means we were generating way too many (RED)
# 3. Alot of correctable errors (RED)
# 4. Some correctable errors (YELLOW)
# 5. No errors (GREEN)


use AM::Util;
use HTTP::Date;
use strict;
use CCE;

my %am_states = am_get_statecodes();

my $time = time;
my $cutoff = $time - (24 * 3600); # look as far back as one day
my $line_date;

if (!defined($ENV{uncorrThreshold})) {
    $ENV{uncorrThreshold} = 0;
}

if (!defined($ENV{corrTheshold})) {
    $ENV{corrTheshold} = 10;
}

my $throttled = 0;
my $uncorrectable = 0;
my $correctable = 0;
my %badchips;
# what was our last error? used in "last error repeated" cases
my %last = (tx => 0,
	    rx => 0,
	    add_error => 0,
	    dram_uncorr => 0,
	    dram_corr => 0,
	    shutdown => 0
	    );


open KERNEL, "</var/log/kernel";

# order of tests here is important for efficiency
# filter as much as possible to avoid doing work
# skip if not looking at important lines
# skip if date is too old
# zero out errors then skip if reboot
# otherwise, do full parsing

while (<KERNEL>) {
    # skip if not looking at important lines
    if (! /NMI:/ && ! /klogd/) {
	next;
    }

    /(\w+\s+\d+\s+[\w:]+)/;
    $line_date = str2time($1);

    # skip if date is too old
    if ($line_date < $cutoff) {
        next;
    }

    # don't worry about errors before the last reboot
    if (/klogd.* started/) {
	$uncorrectable = $correctable = 0;
	next;
    }

    # ORDER OF TESTS IS IMPORTANT HERE!
    # must check for "last error" before overwriting the $last array
    if (/NMI:/) {
	if (/last error repeated (\d+) times/) {
	    my $times = $1;
	    $uncorrectable += $last{tx} * $times;
	    $uncorrectable += $last{rx} * $times;
	    $uncorrectable += $last{dram_uncorr} * $times;
	    $uncorrectable += $last{add_error} * $times;

	    $correctable += $last{dram_corr} * $times;
	    next;
	}

	$last{tx} = /PCI tx data error/;
	$last{rx} = /PCI rx data error/;
	$last{add_error} = /PCI address error/;
	$last{dram_uncorr} = /DRAM uncorrectable/;
	$last{dram_corr} = /DRAM correctable/;
	$last{shutdown} = /Shutdown cycle detected/;

	if (/messages were throttled/) {
	    $throttled = 1;
	    next;
	}

	if (/PCI tx data error/) { $uncorrectable++; }

	if (/PCI rx data error/) { $uncorrectable++; }

	if (/PCI address error/) { $uncorrectable++; }

	if (/DRAM uncorrectable/) { $uncorrectable++; } 

	if (/DRAM correctable/) { $correctable++; }

	if (/Shutdown cycle detected/) { # no one knows what this error means
	}

	# Take note of all of the bad chips.
	if (/memory row (\d+)/) {
	    # We want 0 for 0,1; 1 for 2,3; 2 for 4,5; etc.  Basically,
	    # the memory row div 2.
 	    my $thischip = int($1 / 2);
	    $badchips{$thischip} = 1;
	}
	
    }
}

my $chipstring = "";
if ($badchips{0} + $badchips{1} + $badchips{2} + $badchips{3}) {
	$chipstring .= ",bad=\"" . join (', ', keys(%badchips)) . "\"";
}

# ORDER OF TEST HERE IS IMPORTANT
# worst case is uncorrectable
# then way too many correctable
# then lots of correctable
# then some correctable
if ($uncorrectable > $ENV{uncorrTheshold}) {
    my $cce = new CCE();
    $cce->connectuds();
    my ($am_oid) = $cce->find('ActiveMonitor');
    my ($ok) = $cce->set($am_oid, 'ECC', { 'sysfault' => 1});
    print("[[base-am.uncorrMemErrors$chipstring]]");
    exit $am_states{AM_STATE_RED};
}

if ($throttled || ($correctable > $ENV{corrTheshold})) {
    my $cce = new CCE();
    $cce->connectuds();
    my ($am_oid) = $cce->find('ActiveMonitor');
    my ($ok) = $cce->set($am_oid, 'ECC', { 'sysfault' => 1});
    print("[[base-am.tooManyMemErrors$chipstring]]");
    exit $am_states{AM_STATE_RED};
}

if ($correctable > 0 && $correctable < $ENV{corrTheshold}) {
    print("[[base-am.someCorrMemErrors$chipstring]]");
    exit $am_states{AM_STATE_YELLOW};
}

print("[[base-am.amECCOK]]");
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
