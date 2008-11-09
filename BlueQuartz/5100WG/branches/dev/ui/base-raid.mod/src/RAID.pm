#
# $Id: RAID.pm 201 2003-07-18 19:11:07Z will $
#
# Software RAID 1 UI interface
# Will DeHaan <will@cobalt.com>
#
# Copyright 2000 Cobalt Networks http://www.cobalt.com/
#
use strict;
use warnings;
package Cobalt::RAID;

use vars qw(@ISA @EXPORT @EXPORT_OK);

require Exporter;
use SelfLoader 'AUTOLOAD';

@ISA    = qw(Exporter SelfLoader);
@EXPORT = qw(raid_get_state raid_get_numdisk raid_changelevels
	     raid_fail_drive drive_in_partition required_disks safe_to_fail
	     raid_get_raidtab drive_get_raidlevel
	    );

my $Debug = 0;

1;
__DATA__

sub raid_get_state
# THIS EXPECTS A 2.4 KERNEL FORMATTED /proc/mdstat!!
#
# Test raid status on a partition, estimates progress and completion
# Returns a hash reference of raid devices and their properties
# The hash reference will have the following structure:
#
# $hash->{$drive}->{blocks}        9920
#               |->{failed_drives} reference to array (hde1,hde2)
#               |->{syncing}       recovery|resync
#               |->{percent_done}  40 = 40% done
#               |->{eta}           1.2 = 1.2 minutes left
#               |->{alive_drives}    reference to array (all present and non-failed drives)
#               |->{raidtype}      raid0|raid1|raid5
#               |->{total_num}     total number of drives in partition
#               |->{active_num}    live number of drives in partition
# or undef, if there is no raid support
#
# Some of these properties may not be set. For example, if the
# drive is perfectly fine, neither failed drive nor syncing nor incomplete will be set.
#
#
{
    my $hash = undef;
    my %devices;
    my %raidtab = raid_get_raidtab();

    open(DEVICES, '/proc/devices') or return undef;
    while (<DEVICES>) {
	next unless /(\d+)\s+(\S+)/;
	$devices{int($1)} = $2;
    }

    open(RAID, "/proc/mdstat") or return undef;
    while (<RAID>) {

	# process each raid array
	if (/^md/ ... /^\s*$/) {
	    chomp;
	    if (/^md(\d+)/) {
		# new raid array, reset some variables
		$raid_device = "/dev/md$1";
		$hash->{$raid_device} = undef;
		$failed_drives = [];
		$alive_drives = [];
	    }
	    if (/(\d+) blocks/) {
		$hash->{$raid_device}->{blocks} = $1;
	    }
	    
	    # we're trying to find which drives are ALIVE

	    # split on spaces that do NOT have '[dev' in front of them
	    # dev entries look like '[dev 01:00][1]' and otherwise 
	    # the space would mess things up
	    # man perlre to figure this regex out
	    @pieces = split(/(?<!\[dev) +/, $_);
	    foreach $piece (@pieces) {

		# if a drive is explicitly marked as failed, skip it for now
		if ($piece =~ /\(F\)/) {
		    next;
		}

		# these drives are alive
		if ($piece =~ /^(hd[a-z](\d*))/) {
		    $dev = '/dev/' . $1;
		    push @$alive_drives, $dev;
		    next;
		}
		if ($piece =~ /\[dev (\d+):(\d+)\]/) {
		    $dev = '/dev/' . $devices{int($1)} . int($2);
		    push @$alive_drives, $dev;
		    next;
		}

		# which raid type are we
		if ($piece =~ /(raid(\d+))/) {
		    $hash->{$raid_device}->{raidtype} = $1;
		    next;
		}
	    }

	    # now trying to find syncing status
	    if (/(recovery|resync)\s*=\s*(\d+).*finish=(\d+\.*\d+)min/) {
		$hash->{$raid_device}->{syncing} = $1;
		$hash->{$raid_device}->{percent_done} = $2;
		$hash->{$raid_device}->{eta} = $3;
	    } elsif (/(recovery|resync).*DELAYED/) {
		$hash->{$raid_device}->{syncing} = $1;
		$hash->{$raid_device}->{percent_done} = 0;
		$hash->{$raid_device}->{eta} = "delayed";
	    }

	    # need to keep track of devices where not all
	    # drives are [UUU] up.
	    if (/\[([U_]+)\]/ && !($1 =~ /^U+$/)) {
		$hash->{$raid_device}->{incomplete} = 1;
	    }

	    # this is nasty
	    # the only way to detect if we've seen all the data about
	    # this raid device is
	    # 1) blank line
	    # 2) or we're on a recovery line

	    # now, let's post-process
	    if (/^\s*$/ || $hash->{$raid_device}->{syncing}) {
		
		#mark all good drives
		my %count = ();
		foreach my $drive (@$alive_drives) {
		    $count{$drive}++;
		}
		
		#all drives in /etc/raidtab
		@all_drives = @{$raidtab{$raid_device}}; 
		foreach my $drive (@all_drives) {
#		    print "$drive\n";
#		    print "count is $count{$drive}\n";
		    # if it's not marked as good, then it's bad or missing
		    if (!$count{$drive}) {
			push @$failed_drives, $drive;
		    }
		}
		
		if (@$failed_drives) {
		    $hash->{$raid_device}->{failed_drives} = $failed_drives;
		}
		if (@$alive_drives) {
		    $hash->{$raid_device}->{alive_drives} = $alive_drives;
		}

		$hash->{$raid_device}->{active_num} = scalar(@$alive_drives);
		$hash->{$raid_device}->{total_num} = scalar(@all_drives);

	    }
	    
	    # done with this raid device
	}
    }

    return $hash;
}

sub raid_get_numdisk
# See how many disks we have in /dev/md1; raid_get_state() reports for md4
#
# Invoked in the setup wizard for /home setup
# Arguments: none
# Return value:
{

  open(RAID, "/proc/mdstat") || return (6, '[ha.str.noraid]');
  # We use md1 because that will always be setup to the "reliable" RAID mode
  while(<RAID>) {
      if (/^md1\b/ ... /^\s*$/) {
	  if (/raid0/) {
	      # we only ship raid0 on systems with 1 disk
	      return 1;
	  }
	  if (/blocks.*\[(\d)\/(\d)\].*\[.*\]/) {
	      return $1;
	  }
      }
  }

  return 0;
}

sub raid_changelevels
# runs a shell script to change the RAID level
# Arguments: raid level
# Returns: exit code of script, 0 on success, 1 on failure
{

  my $level=shift;

# ROLLBACK SPECIAL
  system("sh /usr/local/sbin/make_raid.sh $level > /tmp/raiderr 2>&1 &");
  return $? >> 8;

}


# sets a physical drive in a raid array as faulty
# to allow for hot remove of the drive
# Arguments: raid partition, physical drive
sub raid_fail_drive
{
    my ($partition, $drive) = @_;
    # set drive faulty
    my @command = ('/sbin/raidsetfaulty');
    push @command, $partition, $drive;
    # raidsetfaulty is broken
    # pretty much always returns success
    system(@command);
}



# Returns the partitions of the specified drive 
# that exist on the raid device
# In a scalar context, returns the number of times
# examples:
#    /dev/md1, made of hde1, hdi1
# drive_in_partition('hde', $partition) would return (hde1),
# or 1 in scalar context
#
# and in the stupid case of 
#    /dev/md1, made of hde1 hde2
# drive_in_partition('hde', $partition) would return (hde1,hde2).
# or 2 in scalar context.
# 
sub drive_in_partition {
    my ($drive, $partition) = @_;

    if (not defined($partition->{alive_drives})) {
	return 0;
    }

    my @all = @{$partition->{alive_drives}};
    my @matches = ();

    foreach my $part (@all) {
	if ($part =~ /$drive/) {
	    push @matches, $part;
	}	
    }

    if (wantarray) {
	return @matches;
    } else {
	return scalar(@matches);
    }
}

# tell how many drives are needed to maintain 
# data integrity
# Aruguments: a raid type, number of total disks
sub required_disks {
    my ($type, $total) = @_;
    if ($type eq 'raid0') { return $total;}
    elsif ($type eq 'raid1') { return 1;}
    elsif ($type eq 'raid5') { return $total - 1;}
}

# tell if it is safe to fail this $drive for the partition in $part_info
# the $drive may not even be part of the partition
# conditions for failure are
# 1. partition is >= raid1
# 2. failing this drive WILL NOT drop us below the minimum
#    for data integrity
sub safe_to_fail {
    my ($drive, $part_info) = @_;

    my $num_times = drive_in_partition($drive, $part_info);
    if (0 == $num_times) {
	# drive isn't even part of us
	# so it won't even affect this partition
	# sure, we can fail it
	return 1;
    }

    my $type = $part_info->{raidtype};
    # we can't fail raid0 or we lose data
    if ($type eq 'raid0') {
	return 0;
    }
    
    my $total = $part_info->{total_num};
    my $remaining = $part_info->{active_num};
    
    # look at me! i handle the stupid case!
    # the raid partition may have multiple physical
    # partitions from the SAME physical disk
    # doesn't gain anything of course, but it's possible
    # (see drive_in_partition for details)
    #
    # so we'd be failing that partition multiple times
    if ($remaining - $num_times < required_disks($type, $total)) {
	return 0;
    }

    return 1;
}

sub raid_get_raidtab
{
    my %hash = ();
    my ($null, $partition);
    my ($drives, $device);
    open(RAIDTAB, '/etc/raidtab') or die "Couldn't open raidtab";
    while (<RAIDTAB>) {
	if (/raiddev/ ... /^\s*$/) {
	    if (/raiddev/) {
		chomp;
		($null, $partition) = split /\s+/, $_;
		$drives = [];
	    } elsif (/device\b/) {
		chomp;
		($null, $device) = split /\s+/, $_;
		push @{$drives}, $device;
	    } elsif (/^\s*$/ || eof) {
		$hash{$partition} = $drives;
	    }
	}
	
    }
    close(RAIDTAB);
    return %hash;
}


# Get the lowest raid level of a drive from /etc/raidtab.
# (If /dev/hda contains partitions of both raid1 and raid0,
# we pick only the raid0.)
sub drive_get_raidlevel
{
    my %drive_levels = ();
    my ($null);
    my ($device, $level);
 
    open(RAIDTAB, '/etc/raidtab') or die "Couldn't open raidtab";
    while (<RAIDTAB>) {
        if (/raiddev/ ... /^\s*$/) {
            if (/raid-level/) {
                chomp;
                ($null, $level) = split /\s+/, $_;
            } elsif (/device\b/) {
                chomp;
                ($null, $device) = split /\s+/, $_;
                $device =~ /(hd[a-z])\d*/;
                if (exists $drive_levels{$1}) {
                    if ($level < $drive_levels{$1}) {
                        $drive_levels{$1} = $level;
                    }
                }
                else {
                        $drive_levels{$1} = $level;
                }
            }
        }
        
    }
    close(RAIDTAB);
    return %drive_levels;
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
