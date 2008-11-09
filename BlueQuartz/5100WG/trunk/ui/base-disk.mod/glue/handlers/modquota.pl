#!/usr/bin/perl -w -I/usr/sausalito/perl/

sub dir2dev {
        my $dir = shift;
        my @data = `/bin/df $dir 2>&1`;
        my ($dev) = ($data[1] =~ m/^(\S+)/);
        return $dev;
}

my @devs = ( 
        dir2dev('/home'),
#        dir2dev('/var'),
);

# Assuming 1Kb blocks. Is this corrent ?
my $BlocksPerMB = 1024;

use CCE;
use Quota;
use strict;

my $cce = new CCE( Domain => 'base-disk' );
my $error;

$cce->connectfd(\*STDIN,\*STDOUT);

$error = setquota($cce);

if( $error ) {
	$cce->warn($error);
	$cce->bye("FAIL");
	exit 1;
} else {
	$cce->bye("SUCCESS");
	exit 0;
}


sub setquota
# Set the quota for a user or group.
# Arguments: user/group name limit, type
# Side effects: modifies the quota tables
# Returns: 0 for success, a string explaining failure otherwise.
{
	my $cce = shift;

	my $obj = $cce->event_object();

	my $type = $obj->{CLASS};
	my $name = $obj->{name};

	my $oid = $cce->event_oid();
	my ($ok, $disk) = $cce->get($oid, "Disk");
	my $limit = $disk->{quota};
	
	my $id;

	# user or group
	if( $type eq 'User' ) {
		$type = 0;
		$id = getpwnam($name);
		if( ! $id ) {
			$cce->warn("couldNotGetId", 
				{ id => 'GID', target => $name } );
		}
	} else {
		$type = 1;
		$id = getgrnam($name);
		if( ! $id ) {
			$cce->warn("couldNotGetId", 
				{ id => 'GID', target => $name } );
		}
	}

	foreach my $dev ( @devs ) {
		if ( Quota::sync($dev) && ($! != 1) ) {
			return("couldNotSync");
		}
		my ($softquota, $hardquota, $softinode, $hardinode);
		if ($limit eq 0) { # no quota is really quota for one file
			$softquota = 1;
			$hardquota = 1;
			$softinode = 1;
			$hardinode = 1;
		} elsif ($limit gt 0) {
			$softquota = $limit * $BlocksPerMB;
			$hardquota = $softquota + $BlocksPerMB;
		} # else limit < 0 == unlimited quota
		my @args = ($dev, $id, $softquota, $hardquota,
			$softinode, $hardinode);

		# harris used extra arg $type here, which is not documented
		# to let it set quotas for groups too --pbaltz
		if( $type ) {
			# use 0 (defaults to this anyway), "" still gives error
			push @args, 0; 
			push @args, $type;
		}

		my $ret = Quota::setqlim(@args);
		if( $ret != 0 ) {
			print STDERR "Quota Error " . Quota::strerr() . "\n";
			return("couldNotSetQuota");
		}
		if (Quota::sync($dev) && ($! != 1)) {
			return("couldNotResync");
		}
	}

   	return 0;
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
