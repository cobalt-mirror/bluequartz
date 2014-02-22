#!/usr/bin/perl
# $Id: cce_lock.pl,v 1.5.2.2 2002/01/12 02:38:59 pbaltz Exp $
#
# Copyright 2001 Sun Microsystems, Inc., All rights reserved.
#
# (Un)Lock CCE or syncronize to the stored value.
#

my $HELP = <<EOF;
 cce_lock.pl - CCE read-only lock control

 Command line arguments:
 	--sync or none	syncronize CCE to the System.DesktopControl.lock property
	--lock or -l 	lock CCE (suspend to read-only mode)
	--unlock or -u 	unlock CCE (resume read-write mode)
	--reason	The reason CCE is being locked
	--verbose or -v	verbose output

 Specify either -l or -u but not both.

EOF

use lib qw(/usr/sausalito/perl);

my $opt_lock;
my $opt_sync;
my $opt_reason;
my $DEBUG;
my $ok;

# Process any command line options
use Getopt::Long;
$ok = &GetOptions('lock!' => \$opt_lock, 'reason:s' => \$opt_reason,
		  'sync!' => \$opt_sync, 'unlock!' => \$opt_unlock,
		  'verbose!' => \$DEBUG);
die $HELP if (!$ok);

# If neither lock nor unlock were set, then we will sync with CCE 
$opt_lock = 0 if ($opt_unlock);
$opt_sync = 1 if (! defined($opt_lock));

# FIXME: i18n
if ($opt_lock && $opt_sync) {
	# We cannot lock and sync at the same time.
	die $HELP;
}

# Connect to CCE
use CCE;
my $cce = new CCE;
$cce->connectuds();

# Get the current suspended state from CCE
my $suspended = $cce->suspended();
if (defined $suspended) {
	$DEBUG && warn "CCE is suspended.  Reason: \"$suspended\".\n"; 
} else {
	$DEBUG && warn "CCE is currently unlocked.\n";
}

# Determine the action we are going to take
if ($opt_sync) {
	#
	# Syncronize with the saved state.  First, get the DesktopControl
	# property from CCE.
	#
	my $oid;
	my $obj;
	my $locked = -1;
	$DEBUG && warn "Syncronizing lock state to CCE.\n";
	my (@sysoids) = $cce->find('System');
	foreach $oid (@sysoids) {
		($ok, $obj) = $cce->get($oid, 'DesktopControl');
		if ($locked < 0) {
			# We have not found a lock status yet.  Set it now.
			$locked = $obj->{'lock'};
		}
		# Or all the found locks together.  Why not? :)
		$locked |= $obj->{'lock'};
	}

	if ($locked < 0) {
		warn "Could not find CCE lock status\n";
		exit -1;
	}

	if ($locked == 0 && defined($suspended)) {
		# Unlock CCE, since it is suspended now
		$DEBUG && warn "Unlocking CCE.\n";
		$ok = $cce->resume();
	} elsif ($locked == 1 && !defined($suspended)) {
		# Lock CCE, since it is not suspended now
		$DEBUG && warn "Locking CCE.\n";
		$ok = $cce->suspend($opt_reason);
	}

	if (!$ok) {
		warn "Could not syncronize the lock status!\n";
	} else {
		$DEBUG && warn "CCE lock state is syncronized to: $locked\n";
	}

} elsif ($opt_lock == 1) {
	if (defined $suspended) {
		# We are locked already.  Declare success and exit.
		$ok = 1;
		$DEBUG && warn "CCE was already locked.\n";
	} else {
		# Lock CCE with the given reason
		$ok = $cce->suspend($opt_reason);
		if (!$ok) {
			warn "Could not suspend CCE\n";
		}
		$DEBUG && warn "CCE was locked.\n";
	}

} elsif ($opt_lock == 0) {
	if (!defined $suspended) {
		# We are unlocked already.  Declare success and exit.
		$ok = 1;
		$DEBUG && warn "CCE was already unlocked.\n";
	} else {
		# Unlock CCE
		$ok = $cce->resume();
		if (!$ok) {
			warn "Could not resume CCE\n";
		}
		$DEBUG && warn "CCE is unlocked.\n";
	}

} else {
	my $msg = 'unlocked.';
	if (defined $suspended) {
		$msg = 'locked';
	}

	# Should not happen
	warn "ILLEGAL condition: Could not determine action to take.  CCE remains $msg.\n";
	$ok = 0;
}

$cce->bye('SUCCESS');

exit !$ok;

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
