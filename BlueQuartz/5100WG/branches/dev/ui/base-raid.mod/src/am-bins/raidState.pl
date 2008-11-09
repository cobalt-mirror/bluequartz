#!/usr/bin/perl
# $Id: raidState.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# This script returns the state of RAID.
# It will report raid errors for ALL raid partitions.

# The return code is the standard ActiveMonitor statecode.
# The i18n tags it prints out contain extra information:
#    in the case of failure: which partition failed and if possible,
#                            which physical drive failed
#    in the case of syncing: which partition is syncing and 
#                            the percentage and time remaining

use lib qw(/usr/sausalito/perl);
use Cobalt::RAID;
use AM::Util;
use Getopt::Std;
use strict;
use vars qw/ $opt_w /;
use CCE;
use File::Basename;

my %am_states = am_get_statecodes();
my $raid_state = raid_get_state();
my %drive_raidlevels =  drive_get_raidlevel();

# no raid = grey
if (not defined($raid_state)) {
    print "[[base-raid.raid_disabled]]";
    exit $am_states{AM_STATE_NOINFO};
}

# failed drives = red
my %failed = ();
my $failed_drives = 0;
foreach my $drive (keys %$raid_state) {
    my $drive_state = $raid_state->{$drive};
    
    if ($drive_state->{failed_drives} && !$drive_state->{syncing}) {
	$failed_drives = 1;
	foreach my $one_failed (@{$drive_state->{failed_drives}}) {
	    $one_failed =~ s/\d*$//;
	    $failed{$one_failed} = 1;
	}
    }
}
if ($failed_drives) {
    my $cce = new CCE();
    $cce->connectuds();
    my ($am_oid) = $cce->find('ActiveMonitor');
    my ($ok) = $cce->set($am_oid, 'RAID', { 'sysfault' => 1});
    my @output = ();
    
    my $system = `cat /proc/cobalt/systype`;
    chomp($system);

    foreach my $drive (keys(%failed)) {
	# fileparse is like basename
	# gets "hda" from "/dev/hda"
	($drive) = fileparse($drive);
	# the drives="etc" at the end is not displayed at all
	# but the tag is parsed by the AM raid display page
	# as a way to get info to the UI

    	if ($drive_raidlevels{$drive} eq '0') {
		push @output, "[[base-raid.raid0_failure_email_" . $system . "_" . $drive . ",drives=\"" . join (', ', keys(%failed)) . "\"]]";
	}
	else {
		push @output, "[[base-raid.raid_failure_email_" . $system . "_" . $drive . ",drives=\"" . join (', ', keys(%failed)) . "\"]]";
	}
    }
    print join("\n", @output);
    exit $am_states{AM_STATE_RED};
}


# syncing drives = yellow
my $syncing = 0;
my ($total_blocks, $blocks_done, $remaining_blocks, $blocks_done_this_drive,
    $remaining_blocks_this_drive, $per_block_eta, $percentage_done, $eta);
my (%syncing_drives, @drives);
my %raidtab = raid_get_raidtab();

foreach my $drive (keys %$raid_state) {
    my $drive_state = $raid_state->{$drive};
    # to find percentage, we
    # 1. find total number of blocks in all raid devices
    # 2. find number of completed blocks in all raid devices. 
    #    drives that don't need to sync are considered "completed". 
    # 3. divide them, and we have the percentage
    #
    # to find eta,
    # 1. find remaining blocks on the currently syncing drive
    # 2. divide ETA for this drive by remaining blocks to get per-block-eta
    # 3. multiply per-block-eta by total number of blocks in all raid devices
    #    and we have the eta
    #
    # We can't simply take the percentage and time from /proc/mdstat
    # because those numbers apply only to a SINGLE partition.
    # Those number won't help if we have multiple partitions that need to sync,
    # and some are delayed. Doing the per-block-eta method gets us around 
    # that problem.

    if ($drive_state->{syncing}) {
	$total_blocks += $drive_state->{blocks};

	$syncing = 1;

	# find blocks done for this partition
	$blocks_done_this_drive = $drive_state->{percent_done} * $drive_state->{blocks} / 100; 
	$blocks_done += $blocks_done_this_drive;

	$remaining_blocks_this_drive = $drive_state->{blocks} - $blocks_done_this_drive;
	$per_block_eta = $drive_state->{eta} / $remaining_blocks_this_drive;

	# keep track of which physical drives are being synced
	foreach my $drive_in_partition (@{$raidtab{$drive}}) {
	    $drive_in_partition =~ s/\d*$//;
	    $syncing_drives{$drive_in_partition} = 1;
	}
    } elsif ($drive_state->{incomplete}) {
	# an incomplete drive that is not syncing
	# means that it SHOULD eventually be syncing, so it's not done yet.
	$total_blocks += $drive_state->{blocks};

	# keep track of which physical drives are being synced
	foreach my $drive_in_partition (@{$raidtab{$drive}}) {
	    $drive_in_partition =~ s/\d*$//;
	    $syncing_drives{$drive_in_partition} = 1;
	}
    } else {
	# drive isn't syncing, so all it's blocks are "completed"
	$blocks_done += $drive_state->{blocks};
	$total_blocks += $drive_state->{blocks};
    }

}
if ($syncing) {
    $percentage_done = $blocks_done / $total_blocks;
    $remaining_blocks = $total_blocks - $blocks_done;
    $eta = $per_block_eta * $remaining_blocks;
    
    #rounding
    $percentage_done = sprintf("%.f", $percentage_done * 100);
    $eta = sprintf("%.f", $eta);

    @drives = keys %syncing_drives;

    print "[[base-raid.raid_sync_in_progress_email,drives=\"" . join(", ", @drives) . "\",percent=\"$percentage_done\",eta=\"$eta\"]]";
    exit $am_states{AM_STATE_YELLOW};
}

my $incomplete = 0;
foreach my $drive (keys %$raid_state) {
    my $drive_state = $raid_state->{$drive};
    if ($drive_state->{incomplete}) {
	$incomplete = 1;
	# something really weird happened
	# we have an incomplete raid array
	# that isn't syncing
	# and that doesn't have failed drives
	# either it's paused while syncing, which means we tell the user to wait
	# or, it's messed up and we tell the user to call tech support
    }
}
if ($incomplete) {
    print "[[base-raid.raid_sync_incomplete_email]]";
    exit $am_states{AM_STATE_YELLOW};
}

# nothing wrong = green
print "[[base-raid.raid_working_raq]]";
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
