#
# $Id: RAID.pm 3 2003-07-17 15:19:15Z will $
#
# Software RAID 1 UI interface
# Will DeHaan <will@cobalt.com>
#
# Copyright 2000 Cobalt Networks http://www.cobalt.com/
#

package Cobalt::RAID;

use vars qw(@ISA @EXPORT @EXPORT_OK);

require Exporter;
require SelfLoader;

@ISA    = qw(Exporter SelfLoader);
@EXPORT = qw(raid_get_state
	    );

my $Debug = 0;

1;
__DATA__

sub raid_get_state
# The User Interface (UI) recognizes pairwise HA status by known numeric states.
# This does not make for the most readable app, refer to this table to make
# sense of state ordering:
#
# State #       State Description                       UI Descriptor
#  0			Unconfigured/disabled/na				gray
#  1 			Enabled, working great (UU)				green
#  2 			Sync (RAID rebuild), non-fatal			yellow
#  3 			Fatal error, single disk operation		red
#
# Arguments: none
# Return value: state code, state-specific parameters
# Parameters: state 0: none
#			  state 1: none
#             state 2: %done, minutes remaining
#             state 3: %done, failed disk (hda or hdc)
{
  my(@raid_state) = &raid_poll_mdstat();

print STDERR join(' ',@raid_state) if $Debug;
print STDERR "\n\n" if $Debug;

  return 0 if ($raid_state[0] == -2); # No raid config

  # disk failures
  return (4, 'hda') if ($raid_state[0] == -10); 
  return (5, 'hdc') if ($raid_state[0] == -12); 
  return 3 if ($raid_state[0] == -1); 

  return 1 if ($raid_state[0] == 101); # No sync, UU

  # Sync in progress
  # returning '2', %complete, int(eta) in minutes
  return (2, @raid_state); 
}

sub raid_poll_mdstat
# Test raid status on a partition, estimates progress and completion
# Arguments: none
# return value: array of integer percentage complete, minutes remaining
#               return value of -1 indicates raid down, -2 indicates no raid,
#               -10 indicates raid down, disk hda failed
#               -12 indicates raid down, disk hdc failed
#               101 indicates up, no syncing
{
  my($ret, @md);
  my($minutes_remaining, $total_blocks, $fin_blocks) = (0,0,0);
  my($recover_pct, $eta) = (0, 60);
  my($synching, %blocks, $blocks_completed, $slave_up, $survivor);
  my($raid_inactive, $raid_seriously_broken, $suspected_survivor) = (1, 0, '');

  open(RAID, "/proc/mdstat") || return (6, '[ha.str.noraid]');
  while(<RAID>)
    {
    my($raiddev) = $1 if (/^md(\d)/);
    $blocks{$raiddev} = $1 if (/ (\d+) blocks/);

    # special case of disk failure post-reboot prior to disk replacement
    if (/hda/ && !/hdc/) {
      $suspected_survivor = 'hda';
    } elsif (!/hda/ && /hdc/) {
      $suspected_survivor = 'hdc';
    }
    
    if (/\(F\)/) { # F is for sync failure
	$raid_seriously_broken = 1;
	$survivor = $1 if (/(hd[a-z])\d\[\d\]\s/);
	$raid_inactive = 0;
    }

    if (/(resync|recovery)=\s*([\d\.]+)\%.+finish=([\d\.]+)/)
      {
      ($recover_pct, $eta) = ($2 * 0.01, $3);
      $fin_blocks = int($recover_pct * $blocks{$raiddev});
      $blocks_completed += $fin_blocks;
      $synching = $raiddev; # Flag a partition in recovery

      # Calculate ETA first as time-per-block
      $eta = $eta / ($blocks{$raiddev} - $fin_blocks) if ($blocks{$raiddev} - $fin_blocks);
      $raid_inactive = 0;
      }
    elsif ((/\[UU\]/) && (!/DELAYED/))
      {
      $blocks_completed += $blocks{$raiddev};
      $slave_up = 1; # flag for any active raid partition
      $raid_inactive = 0;
      }
    }
  close(RAID);

  return (-12, 0) if ($suspected_survivor eq 'hda');
  return (-10, 0) if ($suspected_survivor eq 'hdc');

  # Check flag for noted recovery, return started or completed sync, 0 eta if no
  # recovery is in progress
  return (-2, 0) if ($raid_inactive);

  if ((!$synching && !$slave_up) || $raid_seriously_broken)
    {
    return (-12, 0) if ($survivor eq 'hda');
    return (-10, 0) if ($survivor eq 'hdc');
    return (-1, 0);
    }

  return (101, 0) unless ($synching);

  $total_blocks = $blocks{'6'} + $blocks{'4'} + $blocks{'3'} + $blocks{'1'};

  # Now apply our time-per-block eta to remaining blocks
  $eta = $eta * ($total_blocks - $blocks_completed);
  $recover_pct = int((1000 * $blocks_completed / $total_blocks) + 5)/10 if ($total_blocks != 0.5);
  $eta = 2 if (($recover_pct >=99) && ($eta > 20));
  $recover_pct = 100 if ($recover_pct > 100);

  return($recover_pct, int($eta + 0.5));
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
