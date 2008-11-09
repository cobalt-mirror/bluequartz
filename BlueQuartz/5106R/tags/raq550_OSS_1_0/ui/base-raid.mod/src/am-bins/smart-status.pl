#!/usr/bin/perl
# $Id: smart-status.pl 259 2004-01-03 06:28:40Z shibuya $
#
# This script returns the SMART state of the disks,
#  to be used in active monitor tests
# It sees if there have been any failures or declines in the 
# SMART status and returns accordingly.

# General algorithm:
# get old smart info snapshot and if too old, update
# get current smart info
# if any failures, return red
# if too many changes since snapshot, return yellow
# else return green

use warnings;
use strict;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/raid);
use CCE;
use AM::Util;
use SMART;
use Cobalt::RAID qw(raid_get_state safe_to_fail drive_in_partition raid_fail_drive raid_get_raidtab);

my $DEBUG = 0;

my $cutofftime = time() - 24*3600; # refresh if older than 1 day
my %am_states = am_get_statecodes();

my $cce = new CCE;
$cce->connectuds();

# get snapshot data
my ($am_oid) = $cce->findx('ActiveMonitor');
my ($ok, $smart_obj) = $cce->get($am_oid, 'SMART');
if (!$ok) {
    print('[[base-smart.noAMobject]]');
    exit $am_states{AM_STATE_NOINFO};
}

# see if we need to refresh data
if ($smart_obj->{refresh} < $cutofftime) {
    ($ok) = $cce->set($am_oid, 'SMART', { 'refresh' => time()});
    if (!$ok) {
	print('[[base-smart.errRefreshingSnapshot]]');
	exit $am_states{AM_STATE_NOINFO};
    }
    # re-get object
    ($ok, $smart_obj) = $cce->get($am_oid, 'SMART');
    if (!$ok) {
	print('[[base-smart.errRegettingObject]]');
	exit $am_states{AM_STATE_NOINFO};
    }
    
}

# get hash from cce data
my $snapshot_info = array_to_smart($cce->scalar_to_array($smart_obj->{snapshot}));

# find which disks to use. We want physical disks that 
# make up all Disk objects in CCE. 
# so if the Disk object is a raid device,
# we look in raidtab for the physical disks.
my %disks = ();
my %raidtab = raid_get_raidtab();

my (@oids) = $cce->findx('Disk', {}, { 'device' => '^/dev/' });
foreach my $oid (@oids) {
    my ($ok, $obj) = $cce->get($oid);
    if ($raidtab{$obj->{device}}) {
	foreach my $dev (@{$raidtab{$obj->{device}}}) {
	    $dev =~ s/\d+$//;
	    $disks{$dev} = 1;
	}
    } else {
	my $dev = $obj->{device};
	$dev =~ s/\d+$//;
	$disks{$dev} = 1;
    }
}

my @physicaldrives = keys %disks; # using a hash to remove duplicates

my @output = ();
my @drives_off = ();
my @standalone = ();
my @unsafe = ();
my ($display_names, $drives);
my @names = ();
my $systype = `cat /proc/cobalt/systype`;
chomp($systype);

# look at current info
my ($failures, $curr_info) = get_smart_info(@physicaldrives);

# split failures into critical (red so we disable) and non-critical (yellow so we warn)

my ($warrantied_ids); #hash ref

foreach my $drive (@physicaldrives) {
    next if ($drive !~ /.*\/(hd.)$/);
    my $drivemodel = `cat /proc/ide/$1/model 2> /dev/null`;

    if ($drivemodel =~ /^ST/) {
        $DEBUG && print "$drive is Segate\n";
        @{$warrantied_ids->{$drive}} = ( 4, 5, 7, 10, 12 );

        # the above values are supplied by manufacturer
    }
    elsif ($drivemodel =~ /^WDC/) {
        $DEBUG && print "$drive is Western Digital\n";

        #all models within Western Digital may not have same warrantied ids
        if ($drivemodel =~ /^WDC WD800/ || 
            $drivemodel =~ /^WDC WD400/) {
            $DEBUG && print "$drive is Western Digital WD400 or WD800 model\n";
            @{$warrantied_ids->{$drive}} = ( 1, 3, 5, 7, 10, 11, 200 );

            # the above values are supplied by manufacturer
        }
        else {
            #Provision here for future WDC drive models.
            #(future WDC drives may have different warrantied fields.)
            #But for now, let us hard code them to WD400 & WD800 attributes.
            $DEBUG && print "$drive is Unknown Western Digital model\n";
            @{$warrantied_ids->{$drive}} = ( 1, 3, 5, 7, 10, 11, 200 );

            # the above values are supplied by manufacturer
        }
    }
    else {
        $DEBUG && print "$drive is from unknown maker\n";
        #For now, assume the default type to seagate.
        @{$warrantied_ids->{$drive}} = ( 4, 5, 7, 10, 12 );

        # the above values are supplied by manufacturer
    }
    
}

my ($red_drives, $yellow_drives); #hash refs

foreach my $failed_drive (keys %$failures) {
    foreach my $red_id (@{$warrantied_ids->{$failed_drive}}) {
        if ($failures->{$failed_drive}->{$red_id}) {
            $red_drives->{$failed_drive}->{$red_id} = 1;
            delete($failures->{$failed_drive}->{$red_id}); #delete from the original
        }
    }
}

#Now delete the drives with no red_ids from the $failues
foreach my $failed_drive (keys %$failures) {
    if (keys(%{$failures->{$failed_drive}}) == 0) {
        delete($failures->{$failed_drive}); #delete from the original
    }
}

my (@yellow_drives) = keys(%$failures); # original now holds only yellow failures

if ($red_drives) {
    # we have failures
    # need to check if we can raidsetfaulty this drive
    foreach my $failed_drive (keys %$red_drives) {
	# need to reget after turning off each drive
	# so we keep an up-to-date count of 
	# how many to turn off
	my $raid_state = raid_get_state();

	$DEBUG && print STDERR "$failed_drive is failed\n";
	my $safe_to_fail = 1;
	my $standalone_drive = 1;
	# check all partitions
	# b/c drive failure will affect all raid partitions
	foreach my $partition (keys %$raid_state) {

	    my $part_info = $raid_state->{$partition};

	    if (!safe_to_fail($failed_drive, $part_info)) {
		$safe_to_fail = 0;
		$DEBUG && print STDERR "not safe to fail $failed_drive for $partition\n";
	    }
	    if (drive_in_partition($failed_drive, $part_info)) {
		$DEBUG && print STDERR "drive $failed_drive is in $partition\n";
		$standalone_drive = 0;
	    }
	}

	if ($standalone_drive) {
	    # this SMART-failed drive is not in any
	    # raid partitions, it must be an 
	    # add-storage drive. We can't fail it, so just
	    # warn
	    # this will also handle the case when
	    # the drive does not exist in the system
	    # but is listed in CCE, i.e. when a drive was removed from
	    # the system
	    push @standalone, $failed_drive;
	} elsif (!$standalone_drive && $safe_to_fail) {
	    # FIXME. race condition, do I care?
	    # in all raid partitions, fail all matching physical partitions
	    # that are on the physical drive
	    foreach my $partition (keys %$raid_state) {
		my $part_info = $raid_state->{$partition};
		my @to_fail = drive_in_partition($failed_drive, $part_info);
		foreach my $drive (@to_fail) {
		    raid_fail_drive($partition, $drive);
		    # assume it worked
		    # because raidsetfaulty always returns success
		}
	    }
	    push @drives_off, $failed_drive;
	} elsif (!$safe_to_fail) {
	    push @unsafe, $failed_drive;
	}
    }
    if (@drives_off) {
	$drives = join(', ', @drives_off);
	
	@names = ();
	foreach my $drive (@drives_off) {
	    $drive =~ s/\//_/g;
	    push @names, "[[base-raid.$systype$drive]]";
	}
	$display_names = join(', ', @names);

	push @output, "[[base-smart.turned_off_drives,drives=\"$drives\",display_names=\"$display_names\"]]";
    }
    if (@unsafe) {
	$drives = join(', ', @unsafe);
	
	@names = ();
	foreach my $drive (@unsafe) {
	    $drive =~ s/\//_/g;
	    push @names, "[[base-raid.$systype$drive]]";
	}
	$display_names = join(', ', @names);

	push @output, "[[base-smart.unsafe,drives=\"$drives\",display_names=\"$display_names\"]]";
    }
    if (@standalone) {
	$drives = join(', ', @standalone);
	
	@names = ();
	foreach my $drive (@standalone) {
	    $drive =~ s/\//_/g;
	    push @names, "[[base-raid.$systype$drive]]";
	}
	$display_names = join(', ', @names);

	push @output, "[[base-smart.standalone,drives=\"$drives\",display_names=\"$display_names\"]]";
    }

    # sysfault light on
    my ($ok) = $cce->set($am_oid, 'SMART', { 'sysfault' => 1});
    print(join "\n", @output);
    exit $am_states{AM_STATE_RED};
}

# report non-critical ids as declining drives
if (@yellow_drives) {
    $drives = join(', ', @yellow_drives);
    
    @names = ();
    foreach my $drive (@yellow_drives) {
	$drive =~ s/\//_/g;
	push @names, "[[base-raid.$systype$drive]]";
    }
    $display_names = join(', ', @names);
    
    print("[[base-smart.smartDeclining,drives=\"" . 
	  join(', ', @yellow_drives) . "\",display_names=\"$display_names\"]]");
    exit $am_states{AM_STATE_YELLOW};
}


#check if any values have changed
my ($snap_val, $curr_val);
my $warn = 0;
my @declining = ();

foreach my $drive (@physicaldrives) {
    # we iterate over the drives in the snapshot
    # if a drive is listed in the snapshot, but not current,
    # that means the drive has been taken out since the snapshot.
    # that case was treated above, so we don't skip it here.
    my $drive_problem = 0;
    if ($snapshot_info->{$drive} && !$curr_info->{$drive}) {
	# actually, since it was handled above, we should never 
	# reach this piece of code
	next;
    }

    foreach my $id (keys %{$snapshot_info->{$drive}}) {
	$snap_val = $snapshot_info->{$drive}->{$id};
	$curr_val = $curr_info->{$drive}->{$id};
	# drive SMART values have dropped too much since last snapshot
	if ( ($snap_val - $curr_val) > $smart_obj->{threshold} ) {
	    $drive_problem = 1;
	}
    }
    if ($drive_problem) {
	$warn = 1;
	push @declining, $drive;
    }
}

if ($warn) {
    $drives = join(', ', @declining);
    
    @names = ();
    foreach my $drive (@declining) {
	$drive =~ s/\//_/g;
	push @names, "[[base-raid.$systype$drive]]";
    }
    $display_names = join(', ', @names);
    
    print("[[base-smart.smartDeclining,drives=\"" . 
	  join(', ', @declining) . "\",display_names=\"$display_names\"]]");
    exit $am_states{AM_STATE_YELLOW};
}

# otherwise everything is OK
print('[[base-smart.smartOK]]');
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
