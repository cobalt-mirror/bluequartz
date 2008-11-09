#!/usr/bin/perl -w
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: 94_add_storage.pl,v 1.14.2.5 2002/03/06 04:04:52 pbaltz Exp $
#
# mount any partitions that are on external storage devices
# if possible.  This needs to run before disk quotas are updated.

use strict;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/storage);
use CCE;
use Base::Disk qw(disk_getumounts disk_getmounts);
use Storage qw(
                storage_mount storage_umount storage_get_uuid
                storage_set_quota
                );
#use LCD qw(put_lcd);
use I18n;

my $DEBUG = 0;
if ($DEBUG)
{
    open(STDERR, ">>/tmp/add-storage.log");
    use Data::Dumper;
}

my $ok;
my $cce = new CCE;
$cce->connectuds();

my $i18n = new I18n;
$i18n->setLocale(I18n::i18n_getSystemLocale());
#put_lcd($i18n->get("[[base-storage.storageAdd1]]"), 
#        $i18n->get("[[base-storage.storageAdd2]]"));

# only get unmounted partitions.  The purpose for this is
# two-fold.  On system boot, only external storage will
# be still unmounted at this point.  On CCE restarting for
# some reason, this prevents us from reattempting to 
# mount partitions that were mounted when this constructor
# ran on boot.
my $umounts = disk_getumounts();

$DEBUG && print STDERR Dumper($umounts);

#
# clean up disk objects for disks that have been removed
# making sure to ignore non-local filesystems
#
my @ext_disks = $cce->find('Disk', { 'internal' => 0 });
for my $ext_disk (@ext_disks) {
	my ($ok, $disk) = $cce->get($ext_disk);
	
	$DEBUG && print STDERR "oid is $ext_disk\n";
	$DEBUG && print STDERR Dumper($disk);

	my $device = $disk->{device};
	if (($device =~ /^\/dev\/\D+$/) && !exists($umounts->{$device})) {
		# block device that's not in umounts, dump it
		$DEBUG && print STDERR "removing $device, oid is $ext_disk\n";
		$cce->destroy($ext_disk);
	} elsif ($device =~ /^\/dev\/.+$/) {
		if (exists($umounts->{$device})) {
			#
			# device is still there, but check if it's the
			# same filesystem
			#
			my $disk_info = { 'device' => $device };
			if (storage_get_uuid($disk_info)) {
				# got the uuid, compare it to this one
				if ($disk_info->{uuid} ne $disk->{uuid}) {
					#
					# uuid's don't match.  Make the disk
					# "new" again by syncing the uuid
					# and trying to reset all the
					# important parameters.  Do these
					# seperately to handle disaster
					# recovery.  Normally the disk should
					# just be reset, but for recovery
					# only the uuid should be synced.  If
					# the disk had sites on it, the reset
					# will fail and be dealt with below.
					# otherwise the disk becomes new
					# storage.
					#
					$cce->set($ext_disk, '', 
						{ 'uuid' => $disk_info->{uuid} });
					my $new_disk = &new_disk($device);
					$cce->set($ext_disk, '', $new_disk);
				}
			} elsif ($disk->{uuid} ne '') {
				# can't read the uuid, but this object has one
				$cce->destroy($ext_disk);
			}
		} else {
			# not in umounts, get rid of it
			$cce->destroy($ext_disk);
		}
	}
}

# so here is the protocol, only mount storage with the mount flag
# set to true if it has a Disk object already.  If it doesn't have
# a disk object, mount it and create a Disk object for it.

# hopefully doing these finds right away gives a performance gain
my %mount = map { $_ => 1 } $cce->find('Disk', { 'mount' => 1, 'internal' => 0 });
$DEBUG && print STDERR Dumper(\%mount);

# now go through umounts and "Do the right thing."
for my $partition (keys %$umounts)
{
    # get the uuid to see if there is a possibility we already know
    # about this disk
    my $disk_info = { 'device' => $partition };
    my $oid = 0;
    
    if ($partition =~ /\D$/) 
    {
        # check if this is just a block device (unpartitioned)
        ($oid) = $cce->find('Disk', { 'device' => $partition });

        if (!$oid)
        {
            # also check to see if this is the block device for partitions
            # that we already know about
            my @parts = $cce->findx('Disk', { 'new' => 0 }, 
                                        { 'device' => "^$partition" });
            my $skip = 0;
            for my $part (@parts)
            {
                ($ok, my $part_info) = $cce->get($part);
                if (storage_get_uuid($part_info))
                {
                    # check to see if we saw this partition already
                    ($oid) = $cce->find('Disk', { 'uuid' => $part_info->{uuid} });
                    if ($oid)
                    {
                        if ($part_info->{mount} && $part_info->{mounted})
                        {
                            # deal with weirdness with mount
                            storage_set_quota($part_info);
                        }
                        $skip = 1;
                        last;
                    }
                }
            }
    
            # skip this block device if necessary
            if ($skip) { next; }
        } # end if (!$oid)
    }
    elsif (storage_get_uuid($disk_info))
    {
        # got a uuid, see if it exists
        ($oid) = $cce->find('Disk', { 'uuid' => $disk_info->{uuid} });
    }

    # hmm, no uuid, that means there was an error or the disk
    # is not partitioned
        
    $DEBUG && print STDERR "for $partition found oid = $oid\n";

    my $disk_stats;

    # see if we already know about this partition, and if it should be
    # mounted, mount it.
    if ($oid)
    {
        $DEBUG && print STDERR "Mounting previously found disk, $partition.\n";

        ($ok, $disk_info) = $cce->get($oid);
        if (!$ok)
        {
            print STDERR "Not mounting $partition, because the information for the partition could not be obtained from CCE.\n";
            next;
        }

        $DEBUG && print STDERR Dumper($disk_info);

        my $check = ($disk_info->{check} ? '' : 'nocheck');
        if ($mount{$oid} 
            && ($disk_stats = storage_mount($disk_info, $check)))
        {
            $DEBUG && print STDERR "Successfully mounted $partition.\n";
            $DEBUG && print STDERR Dumper($disk_stats);

            # update disk information
            ($ok) = $cce->set($oid, '',
                            {
                                'mounted' => 1,
                                'mountPoint' => $disk_stats->{MountPoint},
                                'total' => $disk_stats->{Total},
                                'used' => $disk_stats->{Used}
                            });

            # setup quota
            storage_set_quota($disk_info);
        }
        else
        {
            $DEBUG && $mount{$oid} && print STDERR "Unable to mount $partition.\n";

            # if mounting failed marked it as unmounted
            my $settings = { 'mounted' => 0 };
            
            # unpartitioned devices get updated with the total too
            # in case it is a different disk with the same device name
            if ($partition =~ /[\D]$/)
            {
                $settings->{total} = int($umounts->{$partition});
            }

            ($ok) = $cce->set($oid, '', $settings);
            
            # notify admin somehow maybe just in the ui
        }
    }
    elsif (not $oid)
    {
        $DEBUG && print STDERR "Handling new partition $partition.\n";
	my $new_disk = &new_disk($partition);

        # found a new disk or partition
        $DEBUG && print STDERR Dumper($new_disk);

	($ok, my $badkeys) = $cce->create('Disk', $new_disk);
        if (not $ok) {
            $DEBUG && print STDERR Dumper($new_disk, $badkeys);
            # notify the user somehow
        } else {
            $DEBUG && print STDERR "Successfully created new Disk for $partition.\n";
            $DEBUG && print STDERR Dumper($new_disk);
        }

    }
}

$cce->bye('SUCCESS');
exit(0);

# returns a Disk object with the fields set to a fresh state (for a newly
# discovered disk
sub new_disk
{
	my $device = shift;

	my $new_disk = { 'device' => $device };

	$device =~ /^\/dev\/(.+)$/;
	$new_disk->{label} = $1;
	$new_disk->{new} = 1;
	$new_disk->{total} = int($umounts->{$device});
	# don't mount and not a home partition by default
	$new_disk->{mount} = 0;
	$new_disk->{isHomePartition} = 0;

	# new storage, try to mount it and gather information
	my $disk_stats = storage_mount($new_disk, 'nocheck');
	if (defined($disk_stats)) {
		$DEBUG && print STDERR "Mounted $device successfully.\n";

		# we're like the borg.  new storage that is already
		# mountable gets mounted by default
		my $mounts = disk_getmounts();
		if ($mounts->{$device}->{fs_type} =~ /(ext2)|(xfs)/) {
			$new_disk->{quota} = 1;
		}

		$new_disk->{fsType} = $mounts->{$device}->{fs_type};

		# check if this disk was seen by another cobalt server
		# if (-f "$disk_stats->{MountPoint}/.cobalt_storage")
		# {
		#     $new_disk->{new} = 0;
		# }

		# unmount since we have all the info we need
		storage_umount($device);

		$new_disk->{used} = $disk_stats->{Used};
		$new_disk->{total} = $disk_stats->{Total};

		# get the uuid
		if (!$new_disk->{uuid} && !storage_get_uuid($new_disk)) {
			$DEBUG && print STDERR "Unable to get uuid for $device.\n";
		}
	} else { 
		# if it can't be mounted, blank the uuid and fsType field
		delete($new_disk->{fsType});
		delete($new_disk->{uuid});
	}

	return($new_disk);
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
