#!/usr/bin/perl -I/usr/sausalito/perl
# 
# Name: detectportscans.pl
# Author: Jesse Throwe
# Description: This script parses a phoenix.log file (heavily borrowed from sam)
# and reports the ips currently blocked.
# Copyright 2001 Sun Microsystems, Inc. All rights reserved.
# $Id: detectportscans.pl,v 1.3 2001/10/15 19:03:50 jthrowe Exp $

##################
## Variables

$logfile = "/var/log/phoenix.log";
$timeout = 300;


# we need to find out if scandetection actually isfirewallying.. so we use cce

# initalize CCE
use CCE;

my $cce = new CCE;
$cce->connectuds();

# find our system OID
my @system_oid = $cce->find("System");


# get the Scandetection object
my ($ok, $object, $old, $new) = $cce->get($system_oid[0], 'Scandetection');

# check to make sure CCE/Scandetection object is ok, leave if not
if (!$ok) {
        exit(0);
}

#acquire our variable
my ($paranoiaLevel) = $cce->scalar_to_array($object->{paranoiaLevel});

# and test
if ($paranoiaLevel < 2) { exit 0; }

# now lets actually do the search, sense we are sure we are firewalling

## first lets get the time

($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time - $timeout);

# because month is 0-11, we have to add 1
$mon++;

# becauase we are in the year 2k problem lies
$year = $year - 100;

# more year managling. we have to add a 0 if year is less than 10
if ($year < 10) {
$year = "0$year";
}

# add a 0 to the hour, minute, and second fields if it is less than 10
if ($hour < 10) {
$hour = "0$hour";
}

if ($min < 10) {
$min = "0$min";
}

if ($sec < 10) {
$sec = "0$sec";
}

# now mash them all together
$ourtime = "$year$mon$mday$hour$min$sec";

# open the log
open (LOGFILE, $logfile) or die "unable to open $logfile";

while (<LOGFILE>) {
	
	@entry = split;
	
	# get the log time in plain format
	$logfiletime = $entry[0];
	@localdatetime = split(/-/, $logfiletime);
	@datesplit = split(/\//, $localdatetime[0]);
	@timesplit = split(/:/, $localdatetime[1]);
	$logfiletime = "$datesplit[2]$datesplit[0]$datesplit[1]$timesplit[0]$timesplit[1]$timesplit[2]";
	
	# if the logtime is less than the timeout, further examination is needed
	if ($logfiletime > $ourtime) {
		($iface, $loggingtype, $rest) = split (":", $entry[1]);
		$iface =~ s/[0-9]//;
		if ($iface ne "eth") {
			$blockedips = "";
		} elsif ($entry[2] eq "Firewall") {
			$blockedips = "";
		} elsif ($loggingtype eq "portscan"){	
			($blockedip, $rest) = split ("/", $entry[5]);
			$blockedips = "$blockedips$blockedip\n";
		}
	}
}

close LOGFILE;

print $blockedips;
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
