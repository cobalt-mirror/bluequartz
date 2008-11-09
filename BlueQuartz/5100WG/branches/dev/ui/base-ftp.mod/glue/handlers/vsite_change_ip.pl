#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: vsite_change_ip.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000-2002 Sun Microsystems, Inc., All rights reserved.
#
# When a vsite's ip address changes, make sure there is an FtpSite
# object with the new ip, or if this vsite is the only one using
# the particular FtpSite object, migrate the FtpSite new the new
# ip address.
#

use CCE;

my $DEBUG = 0;

my $cce = new CCE('Domain' => 'base-ftp');
$cce->connectfd();

# some useful variables used throughout
my ($ok, $errors) = (0, 0);

# get vsite info
my $vsite = $cce->event_object();
my $vsite_old = $cce->event_old();

# get vsite's current anon ftp settings
($ok, my $vsite_ftp) = $cce->get($cce->event_oid(), 'AnonFtp');
if (not $ok) {
	$cce->bye('FAIL', '[[base-ftp.cantReadAnonFtp]]');
	exit(1);
}

# get FtpSite info for new and old ip addresses
my ($old_ftp_oid) = $cce->find('FtpSite', { 'ipaddr' => $vsite_old->{ipaddr} });
my ($new_ftp_oid) = $cce->find('FtpSite', { 'ipaddr' => $vsite->{ipaddr} });
$DEBUG && print STDERR "old oid: $old_ftp_oid\tnew oid: $new_ftp_oid\n";

# check for other vsites using the old ip address
my @vsites = $cce->find('Vsite', { 'ipaddr' => $vsite_old->{ipaddr} });
$DEBUG && print STDERR "found ", scalar(@vsites), " sites using the old IP\n";

# check if any other site is still using the old IP
if (scalar(@vsites) == 0 && !$new_ftp_oid) {
	# just migrate the FtpSite
	$DEBUG && print STDERR "migrating FtpSite\n";
	($ok) = $cce->set($old_ftp_oid, '', { 'ipaddr' => $vsite->{ipaddr} });

	if (not $ok) {
		$cce->warn('[[base-ftp.cantUpdateFtpSite]]');
		$errors++;
	}
} else {
	# make sure an FtpSite object exists for the new ip
	if (!$new_ftp_oid) {
		$DEBUG && print STDERR "creating new FtpSite for $vsite->{ipaddr}\n";
		($ok) = $cce->create('FtpSite',
				     {
					'ipaddr' => $vsite->{ipaddr},   
					'enabled' => 1,
				     });
		
		$new_ftp_oid = $cce->oid();
		
		if (not $ok) {
			$cce->warn('createFtpSiteFailed',
				   { 'ipaddr' => $vsite->{ipaddr} });
			$errors++;
		}
	}

	if ($errors == 0) {
		# deal with anonymous ftp migration
		$errors += &migrate_anon_ftp($cce, $vsite, $vsite_ftp,
					     $new_ftp_oid);
	}
}

#
# destroy old FtpSite if necessary, or disable anonymous ftp
# for the old FtpSite if anon ftp is owned by the vsite in question
# plus only do this if the vsite is associated with
# a different FtpSite object now
#
if ((scalar(@vsites) == 0) && $new_ftp_oid && ($errors == 0)) {
	$DEBUG && print STDERR "destroying old FtpSite, $old_ftp_oid\n";
	$cce->destroy($old_ftp_oid);
} elsif (($errors == 0) && $new_ftp_oid && $vsite_ftp->{enabled}) {
	$DEBUG && print STDERR "disabling anon ftp for old FtpSite\n";
	($ok) = $cce->set($old_ftp_oid, '', { 'anonymous' => 0 });

	if (not $ok) {
		$cce->warn('[[base-vsite.cantUpdateFtpSite]]');
		$errors++;
	}
}

if ($errors > 0) {
	$cce->bye('FAIL');
	exit(1);
} else {
	$cce->bye('SUCCESS');
	exit(0);
}


sub migrate_anon_ftp
{
	my ($cce, $vsite, $vsite_ftp, $new_ftp_oid) = @_;

	my ($ok, $errors);

	if (!$vsite_ftp->{enabled}) {
		# no need to worry if anon ftp not enabled
		return 0;
	}

	# check if another site is using anon ftp
	my ($check_anon) = $cce->find('FtpSite', 
				      { 
				      	'ipaddr' => $vsite->{ipaddr}, 
					'anonymous' => 1 
				      });
	if (defined($check_anon)) {
		# error if another site is using anonymous already
		$cce->warn('migrateAnonFtpImpossible',
			   { 'ipaddr' => $vsite->{ipaddr} });
		$errors++;
	} else {
		# migrate anonymous ftp settings
		$DEBUG && print STDERR "migrating anon ftp settings for ",
				"$vsite->{name} to $new_ftp_oid\n";
		($ok) = $cce->set($new_ftp_oid, '',
				  {
					'anonymous' => 1,
					'quota' => $vsite_ftp->{quota},
					'maxConnections' => 
					    $vsite_ftp->{maxConnections},
					'anonymousOwner' => $vsite->{name},
					'anonBasedir' => $vsite->{basedir}
				  });
		if (not $ok) {
			$cce->warn('[[base-ftp.migrateAnonFtpFailed]]');
			$errors++;
		}
	}

	return $errors;
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
