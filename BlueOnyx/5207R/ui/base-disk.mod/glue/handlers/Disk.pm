#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/disk
# $Id: Disk.pm

package Disk;
use Exporter ();
@ISA = qw(Exporter);
@EXPORT = qw(setquota);

#use vars qw($DEBUG);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

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
	&debug_msg("Creation type is: $type \n");
	&debug_msg("Creation limit is: $limit \n");
	&debug_msg("Creation disk is: $disk \n");
	if ($type eq 'User') {
		$type = 0;
		$id = getpwnam($name);
		if(! $id) {
			&debug_msg("Could not get ID for user: $name \n");
			$cce->warn('couldNotGetId', {id => 'UID',
			    target => $name});
			return 0;
		}

		# volume for users lives in main namespace
		push @dirs, ($obj->{volume} ? $obj->{volume} : $home);
		&debug_msg("Using Volume: $obj->{volume} \n");
	} else {
		$type = 1;
		$id = getgrnam($name);
		if (! $id) {
		    &debug_msg("Could not get ID for group: $name \n");
			$cce->warn('couldNotGetId', 
				{id => 'GID', target => $name});
			return 0;
		}

		# volume for Workgroups and Vsites will be in main namespace
		push @dirs, ($obj->{volume} ? $obj->{volume} : $home);
	}

	# removed as suggested by thockin for raq550 kernel c35
	#if (Quota::sync() && ($! != 1)) {
	#	$DEBUG && warn 'Could not sync quota with OS' . "\n";
	#	$cce->warn('couldNotSync');
	#	return 0;
	#}

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
			&debug_msg("Quota Error: " . Quota::strerr() . " \n");
			&debug_msg("Quota Error args: " . join(' ', @args) . " \n");
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
			" -e 'use Quota; Quota::sync(); " .
			' my $dev ' . "= Quota::getqcarg(\"$dir\"); " .
			' Quota::setqlim($dev, ' .
			"$id, $old_softquota, $old_hardquota, $old_softinode" .
			", $old_hardinode" .
			($type ? ", 0, $type);" : ");") .
			" Quota::sync();'";

		&debug_msg("Rollback: $rollback_cmd \n");
		Sauce::Util::addrollbackcommand($rollback_cmd);
	}

	# removed as suggested by thockin for raq550 kernel c35
	#if (Quota::sync() && ($! != 1)) {
	#	$DEBUG && warn 'Could not sync quota with OS' . "\n";
	#	$cce->warn('couldNotSync');
	#	return 0;
	#}
	
	return 1;
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

1;

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 
