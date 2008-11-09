#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: ups.pl,v 1.20.2.2 2002/01/25 03:12:33 uzi Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# Author: Joshua Uziel

$UPSMONCONF = '/etc/ups/upsmon.conf';
$SYSCONF = '/etc/sysconfig/ups';
$MACFILE = '/etc/ups/wol-macs';

use CCE;
use Sauce::Util;
use Sauce::Service;

my $cce = new CCE;
$cce->connectfd();

my ($ups_ok, $upsobj) = $cce->get($cce->event_oid(), 'UPS');
if (not $ups_ok) {
	$cce->bye('FAIL');
	exit(1);
}

# If we're set as a slave and the master's IP is empty, error.
if (($upsobj->{state} eq 'slave') && ($upsobj->{masterip} eq '')) {
	$cce->bye('FAIL', '[[base-ups.masterisempty]]');
	exit 1;
}

# Check that the master's IP is acceptable if we're a slave.
if ($upsobj->{state} eq 'slave') {
	check_addr($cce, $upsobj->{masterip});
}

# Deal with the MAC address file for wake-on-lan purposes.
my @macs = CCE->scalar_to_array($upsobj->{macs});
do_maclist(@macs);

# Set the ups as it should be... 
do_ups($upsobj->{state},  $upsobj->{masterip},
		$upsobj->{wakeslaves}, $upsobj->{wakedelay});

$cce->bye('SUCCESS');
exit(0);

#---------------------------------------------------------------------------#

# Carry out the UPS configuration on behalf of the caller.
# Arguments:
#	state      - "master" or "slave", or blank for disabled
#	masterip   - in "slave" state only, ip of our master
#	wakeslaves - set whether to WOL wake the slaves
#	wakedelay  - seconds to wait between waking each slave
sub do_ups
{
	my $state = shift;
	my $masterip = shift;
	my $wakeslaves = shift;
	my $wakedelay = shift;

	# Stop UPS as it's currently configured - don't background as there
	# would be a race condition if we don't finish stopping before we
	# start again.
	Sauce::Service::service_run_init('ups', 'stop');
	sleep 2;	# Extra sleep just in case

	# Update our configuration file.
	my %settings;

	if ($state eq 'slave') {
		$settings{'HOST'} = $masterip;
	} else {	# slave
		$settings{'HOST'} = 'localhost';
	}
	$settings{'STATE'} = $state;
	$settings{'WAKESLAVES'} = $wakeslaves ? 'true' : 'false';
	$settings{'WAKEDELAY'} = $wakedelay;

	# Update it in the file.
	my $ret = Sauce::Util::editfile($SYSCONF,
		*Sauce::Util::keyvalue_edit_fcn, '#', '=', undef, %settings);
	unless ($ret) {
		$cce->bye('FAIL', '[[base-ups.cantSysconf]]');
		exit 1;
	}

	# Now do the upsmon config file to tell it which machine to monitor
	# for UPS activity, as well as which mode (master/slave) to run in.
	my $host = $settings{'HOST'};
	my $fn = sub {
		my ($fin, $fout) = (shift, shift);
		my ($st, $ho) = (shift, shift);
		foreach $line (<$fin>) {
			if ($line =~ /^MONITOR/) {
				print $fout "MONITOR $ho 1 suncbltups $st\n";
			} else {
				print $fout "$line";
			}
		}
		return 1;
	};

	# Update it in the file.
	$ret = Sauce::Util::editfile($UPSMONCONF, $fn, $state, $host);
	unless ($ret) {
		$cce->bye('FAIL', '[[base-ups.cantUPSMONconf]]');
		exit 1;
	}

	if ($state) {
		# For some reason, this shouldn't need to be 'nobg', but it
		# fails to run otherwise.  Could be worse -- leave it for now.
		Sauce::Service::service_run_init('ups', 'start', 'nobg');
	}

	system('/sbin/lcdstop');
	system('/etc/rc.d/init.d/lcd-showip');
}

# Take the host list and put it out to a file.
sub do_maclist
{
	my @macs = @_;

	my $fn = sub {
		my ($fin, $fout) = (shift, shift);
		@macs = @_;
		print $fout "# MAC addresses to be woken by /usr/bin/ups-wakeslaves.\n";
		foreach $mac (@macs) {
			print $fout "$mac\n";
		}
		return 1;
	};

	# Update it in the file.
	my $ret = Sauce::Util::editfile($MACFILE, $fn, @macs);
	unless ($ret) {
		$cce->bye('FAIL', '[[base-ups.cantmacfile]]');
		exit 1;
	}
}

# Check to see that the IP address entered for the master is usable.
sub check_addr
{
	my ($cce, $masterip) = @_;

	# We make sure it's not (0.*.*.*, 127.*.*.* or 255.*.*.*)
	$first = $masterip;
	$first =~ s/\..*//;	# Chop off everything after the first dot
	if ($first eq '0' || $first eq '255') {
		$cce->bye('FAIL', '[[base-ups.masterisinvalid]]');
		exit 1;
	}
	if ($first eq '127') {
		$cce->bye('FAIL', '[[base-ups.masterislocal]]');
		exit 1;
	}

	# Then, we check all system ipaddrs to see if we have a match
	my @netobjs = $cce->find('Network');
	foreach $netobj (@netobjs) {
		my ($net_ok, $net) = $cce->get($netobj);
		if (not $net_ok) {
			$cce->bye('FAIL');
			exit(1);
		}
		if ($masterip eq $net->{ipaddr}) {
			$cce->bye('FAIL', '[[base-ups.masterislocal]]');
			exit 1;
		}
	}
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
