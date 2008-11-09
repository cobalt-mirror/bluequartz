#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/disk
# $Id: Disk.pm 201 2003-07-18 19:11:07Z will $
# Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.

package Disk;
use Exporter ();
@ISA = qw(Exporter);
@EXPORT = qw(setquota);

use vars qw($DEBUG);
$DEBUG = 0;

use CCE;
use Quota;
use Base::HomeDir;
use DiskInfo;
use Sauce::Util;

sub setquota
# Set the quota for a user or group.
# Arguments: cce connection, user or group object, and oid
# Side effects: modifies the quota tables
# Returns: 1 for success, a string explaining failure otherwise.
{
	my ($cce, $obj, $oid) = @_;

	my $type = $obj->{CLASS};
	my $name = $obj->{name};

	my ($ok, $disk, $disk_old) = $cce->get($oid, 'Disk');
	my $limit = $disk->{quota};

	# be explicit about this on destroy to avoid confusion in the future
	if ($cce->event_is_destroy()) {
		# always set to unlimited on destroy
		$limit = -1;
	}
	
	my $id;
	my @dirs = ();
	my $home = $Base::HomeDir::HOME_ROOT;
	my $BlocksPerMB = $DiskInfo::BYTES_PER_BLOCK;
	# user or group
	if ($type eq 'User') {
		$type = 0;
		$id = getpwnam($name);
		if(! $id) {
			$DEBUG && warn 'could not get ID for user ' . $name .
			    "\n";
			$cce->warn('couldNotGetId', {id => 'UID',
			    target => $name});
			return 0;
		}

		# volume for users lives in main namespace
		push @dirs, ($obj->{volume} ? $obj->{volume} : $home);
	} else {
		$type = 1;
		$id = getgrnam($name);
		if (! $id) {
			$DEBUG && warn 'could not get ID for group' . $name .
			    "\n";
			$cce->warn('couldNotGetId', 
				{id => 'GID', target => $name});
			return 0;
		}

		# volume for Workgroups and Vsites will be in main namespace
		push @dirs, ($obj->{volume} ? $obj->{volume} : $home);
	}

	for my $dir (@dirs) {
		my $softquota = 0;
		my $hardquota = 0;
		my $softinode = 0;
		my $hardinode = 0;

		if ($limit eq 0) {
			# no quota is really quota for one file
			$softquota = 1;
			$hardquota = 1;
			$softinode = 1;
			$hardinode = 1;
		} elsif ($limit gt 0) {
			$softquota = $limit * $BlocksPerMB;
			$hardquota = $softquota + $BlocksPerMB;
		} 

		my @args = (Quota::getqcarg($dir), $id, $softquota, $hardquota,
				$softinode, $hardinode);

$DEBUG && warn "Args: ".join(', ', @args)."\n";

		# harris used extra arg $type here, which is not documented
		# to let it set quotas for groups too --pbaltz
		if ($type) {
			# use 0 (defaults to this anyway), "" still gives error
			push @args, 0; 
			push @args, $type;
		}

		#ROLLBACK QUOTA
		my $ret = Quota::setqlim(@args);
		if ($ret != 0) {
			$DEBUG && warn "Quota Error " . Quota::strerr() . "\n";
			$DEBUG && warn "args: ", join(' ', @args), "\n";
			$cce->warn('couldNotSetQuota', {'name' => $name });
			return 0;
		}

		# add rollback for quota
		my $old_softquota = 0;
		my $old_hardquota = 0;
		my $old_softinode = 0;
		my $old_hardinode = 0;
		if ($disk_old->{quota} eq 0) {
			# no quota is really quota for one file
			$old_softquota = 1;
			$old_hardquota = 1;
			$old_softinode = 1;
			$old_hardinode = 1;
		} elsif ($disk_old->{quota} > 0) {
			$old_softquota = $disk_old->{quota} * $BlocksPerMB;
			$old_hardquota = $old_softquota + $BlocksPerMB;
		}

		my $rollback_cmd = "/usr/bin/perl " .
			" -e 'use Quota;" .
			' my $dev ' . "= Quota::getqcarg(\"$dir\"); " .
			' Quota::setqlim($dev, ' .
			"$id, $old_softquota, $old_hardquota, $old_softinode" .
			", $old_hardinode" .
			($type ? ", 0, $type);" : ");") .
			" '";

		$DEBUG && print STDERR $rollback_cmd, "\n";
		Sauce::Util::addrollbackcommand($rollback_cmd);
	}

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
