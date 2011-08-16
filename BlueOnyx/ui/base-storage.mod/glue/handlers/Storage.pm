# $Id: Storage.pm,v 1.14.2.3 2002/03/16 00:10:25 bservies Exp $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# Storage Disk Utility library.  Used by storage module to 
# add and remove storage.  Not for external use.  This is here
# to maintain separation between the disk module which just
# describes disks and this which actually can add/remove disks.

package Storage;

use strict;
use lib qw(/usr/sausalito/perl);
use vars qw(@ISA @EXPORT_OK $DEBUG);

require Exporter;
@ISA = qw(Exporter);
@EXPORT_OK = qw(
                storage_mount storage_umount storage_mountok
                storage_partition storage_checkpartition
                storage_format storage_set_quota
                storage_get_uuid storage_label_link
                storage_get_mount_point
                );

use Base::Disk qw(disk_get_usage_info disk_getmounts);
use Sauce::Util;

# debug flag, set to true to turn on some logging to STDERR
$DEBUG = 0;
if ($DEBUG) { use Data::Dumper; }

# directory and files used
my $VOL_ROOTDIR = '/vol';
my $FSTAB = '/etc/fstab';

# commands used
my $MOUNT = '/bin/mount';
my $UMOUNT = [ '/bin/umount', '-f' ];
my @QUOTAON = ('/sbin/quotaon', '-ug');
my @QUOTAOFF = ('/sbin/quotaoff', '-ug');
my @QUOTACHECK = ('/sbin/quotacheck', '-ug');
my $SFDISK = '/sbin/sfdisk -fqL';
my $FORMAT = {
                'ext2' => [
                            '/sbin/mke2fs', '-q', '-m', 1, '-b', 4096,
                            '-O', 'sparse_super'
                            ],
                'reiserfs' => [ '/sbin/mkreiserfs', '-q' ],
                'xfs' => [ '/sbin/mkfs.xfs', '-q', '-f' ]
             };
my $FSCK = {
                'ext2' => [ '/sbin/e2fsck', '-a' ],
                'reiserfs' => [ '/sbin/reiserfsck', '--check', '--fix-fixable' ],
                'xfs' => [ '/sbin/fsck.xfs' ]
           };
my %QUOTA_SUPPORT = (
                        'ext2' => 1,
                        'xfs' => 1
                    );

my $UUID_OBTAIN = {
                    'ext2' => [ '/sbin/tune2fs', '-l' ],
                    'xfs' => [ '/usr/sbin/xfs_admin', '-u' ]
                    };
                    
# functions

# return the path to a symlink for the passed in label
sub storage_label_link
{
    my $label = shift;
    return "$VOL_ROOTDIR/$label";
}

# return where a disk will be mounted by storage_mount
# takes a Disk object from CCE as it's only argument
sub storage_get_mount_point
{
    return _get_mount_point($_[0]);
}

sub storage_mount
# description: mount a disk or nfs filesystem.
#
# input: ($disk, $flags, $options)
# available flags: nocheck
# available options:  see the mount man page for options for various
#                     filesystems
#
#
# output: hash of disk usage information on success, undefined on failure 
{
    my ($disk, $flags, $options) = @_;
    my $mountpoint;
    my $nfs;
    my $enabled = '';
    my $id;
    
    return undef unless defined($disk);
    return undef unless ($disk->{device} =~ m=^/dev/(\S+)$= or 
             ($nfs = ($disk->{device} =~ m=\S+?:/[\S+/](\S+)$=)));

    $mountpoint = _get_mount_point($disk);

    # make sure the partition is happy
    return undef unless ($nfs or ($flags && $flags =~ /\bnocheck\b/) or
             (not storage_checkpartition($disk)));

    # make sure some directories exist.
    my $umask = umask(002);
    Sauce::Util::makedirectory($VOL_ROOTDIR, 0755) unless -d $VOL_ROOTDIR;
    Sauce::Util::makedirectory($mountpoint, 0755) unless -d $mountpoint;
    umask($umask);

    my @mountopts = ();
    if ($options)
    {
        push @mountopts, '-o', $options;
    }
    
    # try mounting
    if (system($MOUNT, @mountopts, $disk->{device}, $mountpoint))
    {
        # remove mountpoint to keep things clean, mostly important
        # because we have to try to mount block devices in the case
        # of CD drives, because they have partitions
        rmdir($mountpoint);
        return undef;
    }

    $DEBUG && print STDERR "Successfully mounted $disk->{device} on $mountpoint.\n";
    # return the disk information if everything was successful
    my $disk_stats = disk_get_usage_info($mountpoint);
    return $disk_stats->{$disk->{device}};
}

# remount the filesystem of the specified disk object
sub storage_remount
{
    my $disk = shift;

    system($MOUNT, '-o', 'remount', $disk->{device});

    return ($? ? 0 : 1);
}

sub storage_umount
# description: unmount a disk.
# 
# input: $device (or hostname:/dir for nfs), optional $flags
# flags: disable will disable the disk
#
# output: 0 if the umount failed. 1 otherwise.
{
    my $device = shift;
    my $flags = shift;

    return 0 unless $device;

    # make sure the disk is actually mounted
    my $mounts = disk_getmounts();
    if (!exists($mounts->{$device}))
    {
        # already unmounted, just return 1
        return 1;
    }

    return (system(@{$UMOUNT}, $device) ? 0 : 1);
}

sub storage_checkpartition
# description: checks to make sure everything with the disk partition
#              is all right. right now, this just does an fsck. quotacheck
#              really only works on mounted filesystems.
# 
# input: $disk hash + optional @args.
#
# output: return 0 exit status if something went wrong.
{
    my ($disk, @args) = @_;
   
    return -1 unless defined($disk);
  
    if ($DEBUG)
    {
        print STDERR Dumper($disk, $FSCK);
    }
   
    if (!defined($FSCK->{$disk->{fsType}}))
    {
        return 0;
    }

    my @fsck_cmd = @{$FSCK->{$disk->{fsType}}};
    push @fsck_cmd, @args, $disk->{device};
    
    # return fsck's error codes. error codes 0, 1, and 2 are okay.
    # system(@fsck_cmd);
    return 0; # ($? and (($? >> 8) & 0xFC)) ? 0 : 1;
}

sub storage_mountok
# description: return whether or not a mount point is okay to use.
#              this just makes sure that the mountpoint is 
#              writeable and not an nfs mount.    
# input: a path
#
{
    my $path = shift;

    my $umask = umask(002);
    Sauce::Util::makedirectory($path, 0755) unless -e $path;
    umask($umask);

    my $df = disk_get_usage_info($path);
    my @keys = keys %$df;

    return 0 unless scalar(@keys);
    return 0 unless (-d $path and -w $path);
    return 1;
}

sub storage_set_quota
# description: turn on quota support for a partition, the disk should be
#               mounted before calling this
#
# arguments: $disk reference
#
# returns: 1 if successful, 0 if failure, -1 if quotas aren't supported
#
#
{
    my $disk = shift;

    if (not exists($QUOTA_SUPPORT{$disk->{fsType}}))
    {
        $DEBUG && warn("Quotas not supported for $disk->{fsType}");
        return -1;
    }
   
    # need different set quota for ext2 and xfs, because of the way quotas
    # are done
    if ($disk->{fsType} eq 'ext2')
    {
        $DEBUG && warn("Setting quota for ext2 fs on $disk->{device}");
        return _ext2_set_quota($disk);
    }
    elsif ($disk->{fsType} eq 'xfs')
    {
        $DEBUG && warn("Setting quota for xfs on $disk->{device}");
        return _xfs_set_quota($disk);
    }
   
    $DEBUG && warn("Unknown fs, $disk->{fsType}, on $disk->{device}");
    return -1;
}

sub storage_format
# description: format a partition
#
# input: ($partition, $label, optional $flags)
# currently understood flags: check  check for bad blocks    
#
# output: 1 on success, 0 on error.
{
    my ($part, $fs_type, $flags) = @_;
    my ($volmount, $opts);

    return 0 unless $part =~ /^\/dev\/(\S+)/;
    return 0 unless defined($FORMAT->{$fs_type});

    # make sure the disk isn't mounted
    my $mounts = disk_getmounts();
    if (exists($mounts->{$part}))
    {
        $DEBUG && warn("Refusing to format mounted partition, $part!");
        return 0;
    }

    # format.  only allow checks if it is ext2
    if ($fs_type eq 'ext2' && $flags =~ /\bcheck\b/)
    {
        $opts = "-c";
    }
    my @cmd = @{$FORMAT->{$fs_type}};
    system(@cmd, $part);

    $DEBUG && warn(join(' ', @{$FORMAT->{$fs_type}}) . " exited with $?");
    return ($? ? 0 : 1);
}

sub storage_partition
# description: write the partition table. this will over-write any
# existing partition table. in addition, it will only set up the
# primary partitions.
#
# input: $disk (/dev/[sh]d[a-z]), $num_partitions (1-4), $size1, ..., $size4
#
# output: 1 on success, 0 on failure.
{
    my $disk = shift;
    my $num_partitions = shift;
    my $count; 

    if ($disk !~ m"^/dev/\w+[\D]")
    {
        $DEBUG && warn("$disk is not a block device!");
        return 0;
    }

    if ($num_partitions <= 0)
    {
        $DEBUG && warn("invalid number for partitions, $num_partitions, for $disk");
        return 0;
    }

    # normally this is bad, but we never ask the user for a device name
    # in the case of partitioning a disk.  instead we get our info
    # from the kernel
    if (!open(SFDISK, "| $SFDISK -uM $disk >/dev/null 2>/dev/null"))
    {
        $DEBUG && warn("couldn't run $SFDISK: $!");
        return 0;
    }

    for ($count = 0; $count < 4; $count++) 
    { # we only handle primary partitions
        my $line = '';
        if ($count < $num_partitions)
        {
            my $size = shift;
            print SFDISK defined($size) ? ",$size,83\n" : ",,83\n";
        }
        else
        {
            print SFDISK ";\n";
        }
    }
    print SFDISK "y\n"; # write the partition table
    close(SFDISK);
    $DEBUG && warn("$SFDISK returned $?");
    return ($? ? 0 : 1);
}

# get the uuid for a formatted partition
# takes a copy of the disk object from CCE and fills in the uuid field
# should really only be used by the constructor since for xfs
# there seems to be no way to get the uuid of a mounted xfs partition
# returns 1 on success, 0 on error
sub storage_get_uuid
{
    my $disk = shift;

    # blank field
    $disk->{uuid} = '';
   
    my $original_fs = $disk->{fsType};

    my @possible_filesystems;
    if ($disk->{fsType})
    {
        $DEBUG && warn("prepending $disk->{fsType} to list of filesystems");
        push @possible_filesystems, $disk->{fsType};
    }
   
    # always try ext2 first, because xfs_admin returns uuids for
    # other filesystem types even if they aren't xfs (and they are wrong!)
    push @possible_filesystems, 'ext2', 'xfs';

    for my $fs (@possible_filesystems)
    {
        $DEBUG && warn("searching for uuid with $fs filesystem");
        $disk->{fsType} = $fs;
        if ($disk->{fsType} eq 'ext2')
        {
            get_ext2_uuid($disk);
        }
        elsif ($disk->{fsType} eq 'xfs')
        {
            get_xfs_uuid($disk);
        }

        # stop searching if we found the uuid
        if ($disk->{uuid}) { last; }
    }

    if ($disk->{uuid} eq '') 
    {
        $disk->{fsType} = $original_fs;
        return 0; 
    }

    return 1;
}

# private functions
sub edit_fstab
# description: make sure fstab has an entry for quota usage.
# 
# input: $disk reference as returned by $cce->get
#
# output: 1 on success. 0 on error.
{
    my ($in, $out, $disk) = @_;

    return 0 unless defined($disk);
    return 0 unless $disk->{device} && $disk->{mountPoint};

    # we need to mangle what the mount entry looks like;
    my $mountpoint = local_mangle($disk->{mountPoint});

    my $line = "$disk->{device}\t$mountpoint\t$disk->{fsType}\tnoauto";
    if ($disk->{quota})
    {
        if ($disk->{fsType} eq 'ext2')
        {
            $line .= ',usrquota,grpquota,grpid';
        }
        elsif ($disk->{fsType} eq 'xfs')
        {
            $line .= ',usrquota,grpquota,osyncisdsync';
        }
        else
        {
            return 0;
        }
    }
    $line .= "\t0 0";

    my $exist = 0;
 
 	$DEBUG && print STDERR "Device is $disk->{device}\n";
    while (<$in>) 
    {
        $DEBUG && print STDERR $_;
		# re-write same entry as before
        if ($disk->{device} ne '' && /^($disk->{device})\s/) 
        {
			$DEBUG && print STDERR "found $_";
			
            # only include line, if quota is on
            if ($disk->{quota})
            {
				$DEBUG && print STDERR "quota on\n";
                print $out $line, "\n";
            }
            $exist = 1;
        } 
        else
        {
            print $out $_;
        }
    }
   
    if (!$exist && $disk->{quota})
    {
		$DEBUG && print STDERR "Adding quota line\n";
        print $out $line, "\n";
    }

    return 1;
}

# these two routines know how mount-2.9o deals with special characters.
sub local_mangle
{
    my $name = shift;

    return $name unless $name =~ /[\\\s]/;
    $name =~ s/([\\\s])/sprintf("\\%0.3o", ord($1))/ge;
    return $name;
}

sub local_unmangle
{
    my $name = shift;
    return $name unless $name =~ /\\/;
    
    $name =~ s/\\(\d\d\d)/pack("c", oct($1))/ge;
    return $name;
}

sub get_ext2_uuid
{
    my $disk = shift;

    # ext2 allows us to get the uuid while the filesystem is mounted
    if (open(EXT2, '-|') || exec(@{$UUID_OBTAIN->{'ext2'}}, $disk->{device}))
    {
        while (<EXT2>)
        {
            $DEBUG && print STDERR "Read $_";
            if (/UUID:\s+(.*)$/)
            {
                $disk->{uuid} = $1;
                last;
            }
        }

        close(EXT2);
    }
}

sub get_xfs_uuid
{
    my $disk = shift;

    # device must be specified
    $disk->{device} || return;

    my $mounts = disk_getmounts();
    my $umounted = 0;

    # need to unmount to check xfs uuid
    if ($mounts->{$disk->{device}})
    {
        storage_umount($disk->{device}) || return;
        $umounted = 1;
    }

    my $pid = open(XFS, '-|');

    if ($pid == 0)
    {
        exec(@{$UUID_OBTAIN->{'xfs'}}, $disk->{device});
    }
    elsif (defined($pid))
    {
        while (<XFS>)
        {
            $DEBUG && print STDERR "Read $_";
            if (/^uuid\s+=\s+(.*)$/)
            {
                $disk->{uuid} = $1;
                last;
            }
        }
    }

    # remount disk if unmounted above
    if ($umounted)
    {
	# Remount with quotas turned on
	my $check = ($disk->{check}) ? '' : 'nocheck';
        my $options = ($disk->{quota} ? 'usrquota,grpquota,osyncisdsync' : '');
	$DEBUG && warn 'Remounting ' . $disk->{'device'} . ' with ' .
	    $options . "\n";
        storage_mount($disk, $check, $options);
    }
}

sub _get_mount_point
{
	my $disk = shift;

	($disk->{device} =~ m=^/dev/(\S+)$=) ||
        ($disk->{device} =~ m=\S+?:/[\S+/](\S+)$=);

	return "$VOL_ROOTDIR/.$1";
}

sub _ext2_set_quota
{
    my $disk = shift;
    
    # make sure the mountpoint is there for editing the fstab
    $disk->{mountPoint} = _get_mount_point($disk);

    # first add in quota support in fstab
    if (!Sauce::Util::editfile($FSTAB, *edit_fstab, $disk))
    {
        # fail if the edit failed because it handles enable and disable
        return 0;
    }

    $DEBUG && print STDERR `/bin/cat $FSTAB`, "\n";

    my $mounts = disk_getmounts();
    my $mounted = 0;

    # need the disk to be mounted before we do anything else
    if (not exists($mounts->{$disk->{device}}))
    { 
        $DEBUG && warn("$disk->{device} is not mounted, attempting to mount");
        my $disk_stats = storage_mount($disk, 'nocheck', 'usrquota,grpquota');
        if (($disk->{device} =~ /^\/dev\/\D+$/) && !$disk->{quota}) {
		#
		# block device, if it can't be mounted it probably
		# doesn't have a filesystem, which is okay if not trying to
		# turn on quota
		#
		return(1);
	} elsif (!defined($disk_stats)) {
            $DEBUG && warn("unable to mount $disk->{device} to check quota.");
            return 0;
        }

        $disk->{mountPoint} = $disk_stats->{MountPoint};
        $mounted = 1;
    }
    else
    {
        storage_remount($disk);
    }

    if ($disk->{quota})
    {
        # make sure the correct files are in place
        if (! -s "$disk->{mountPoint}/quota.user")
        {
            `/bin/touch $disk->{mountPoint}/quota.user`;
            chmod 0600, "$disk->{mountPoint}/quota.user";
        }

        if (! -s "$disk->{mountPoint}/quota.group")
        {
            `/bin/touch $disk->{mountPoint}/quota.group`;
            chmod 0600, "$disk->{mountPoint}/quota.group";
        }

        system(@QUOTACHECK, $disk->{device}) && return 0;
        system(@QUOTAON, $disk->{device}) && return 0;
    }
    else
    {
        system(@QUOTAOFF, $disk->{device});
    }

    # if we mounted it, unmount it again
    if ($mounted)
    {
        # if unmounting fails, return failure, because something is obviously
        # wrong
        if (!storage_umount($disk->{device}))
        {
            return 0;
        }
    }

    return 1;
}

sub _xfs_set_quota
{
    my $disk = shift;

    # need the mount point
    $disk->{mountPoint} = _get_mount_point($disk);
    $DEBUG && print STDERR Dumper($disk);
    if (!Sauce::Util::editfile($FSTAB, *edit_fstab, $disk))
    {
        return 0;
    }

    my $mounts = disk_getmounts();

    # if the partition was mounted, xfs needs to be remounted to
    # have the new settings take effect
    if (exists($mounts->{$disk->{device}}))
    {
        $DEBUG && warn("$disk->{device} was mounted, attempting to remount");
        if (!storage_umount($disk->{device}))
        {
            $DEBUG && warn("unable to umount $disk->{device} to setup quota.");
            return 0;
        }
        
        my $options = ($disk->{quota} ? 'usrquota,grpquota,osyncisdsync' : '');
        if (!storage_mount($disk, 'nocheck', $options))
        {
            $DEBUG && warn("unable to mount $disk->{device} to setup quota.");
            return 0;
        }
   }

    $DEBUG && print STDERR `/bin/mount`, "\n";

    return 1;
}
1;
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
