#!/usr/bin/perl
# $Id: raidState.pl 3 2003-07-17 15:19:15Z will $
#
# This script returns the state of RAID.
#
# When invoked without any options, the script will return a message
# indicating the RAID status uninterpolated and generalized (if there
# is a failure condition, the message won't specify which drive failed).
# This first mode is used to communicate with Active Monitor.
#
# When invoked with the -w option, the status is returned as a comma
# separated list:
#   Disabled/No info: 0
#   Working:          1
#   Synching:         2,%complete,minutes remaining
#   Drive Failed:     3
#   hda Failed:
#   hdc Failed:


# In both cases, the exit status represents the
#

use Cobalt::RAID;
use AM::Util;
use Getopt::Std;
use strict;
use vars qw/ $opt_w /;

my %am_states = am_get_statecodes();
my @raid_state = raid_get_state();

$raid_state[0] ||= 3; # RAID can not be disabled 

getopts("w");
if ($opt_w) {
    print join(',', @raid_state);
    exit $raid_state[0];
}

my @raid_msg = ("raid_disabled", "raid_working", "raid_sync_in_progress", 
		"raid_failure_qube", 
		"raid_failure_hda_qube", "raid_failure_hdc_qube");

print "[[base-raid.$raid_msg[$raid_state[0]]]]";

if ($raid_state[0] == 2) {
    print " $raid_state[1] % [[base-raid.raid_completed]], $raid_state[2] [[base-raid.raid_minutes_remaining]]";
}

# map raid_state[0] to an Active monitor state
my @am_state_mapping = ("AM_STATE_NOINFO", "AM_STATE_GREEN", "AM_STATE_YELLOW",
			"AM_STATE_RED", "AM_STATE_RED", "AM_STATE_RED");

exit $am_states{$am_state_mapping[$raid_state[0]]};


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
