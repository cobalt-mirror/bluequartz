#!/usr/bin/perl -I /usr/sausalito/perl

package Capabilities;
use strict;
use Data::Dumper;
use CCE;

# loop through all the capLevels this user has

sub new {
  my $proto = shift;
  my $class = ref($proto) || $proto;
  my $self = {};
  bless ($self, $class);
  $self->init(@_);
  return $self;
}

sub init {
  my $self = shift;
  my $cce = shift; 
  $self->{cce} = $cce;

  # cache all the capabilities
  $self->{capabilityGroupCache} = {};
  
  # loop for the cache.
  my @oids = $cce->find("CapabilityGroup");
  for my $oid (@oids) {
    my ($ok, $object) = $cce->get($oid);
    $self->{capabilityGroupCache}{$object->{name}} = $object;
  }
}

# This function is used to verify a list a capabilities against the current
# user's capabilities.   This is done to ensure that a user cannot give another
# user permissions that he doesn't already have.
# returns: () if the caps are valid to be assigned, otherwise return an array
# of the names of the capabilities that stopped that failed the check.
sub checkCaps {
  my $self = shift;
  my $capsToCheck = shift;
  

  my $ret = ();

  # loop and check each capability
  for my $cap (keys %$capsToCheck) {
    if (!$self->getCapable($cap)) {
      push @$ret, $cap;
    }
  }
  return $ret;

}


# This function expands a given list of capabilityGroups into an array
# of cce-level capabilities.
sub expandCaps {
  my $self = shift;
  my $levels = shift;


  my $cce = $self->{cce};

  # groups is a hash of all the groups we've hit so far.  we push this 
  #   down our recursive stack in order to avoid circling around the 
  #   graph.
  my $groups = shift || {};
  # caps on the other hand is a recursive return value that is generated 
  #   while climbing back up the stack.  This looks confusing, but is 
  #   neccesary.
  my %caps;

  # list of all my new caps for the expansion
  for my $level (keys %$levels) {
    # make sure we don't go around the graph in circles!
    if ($groups->{$level}) {
      next;
    }
    # check if this level is a group or not.
    if (! defined $self->{capabilityGroupCache}{$level}) {
      # we hit the bottom of the graph!
      $caps{$level} = 1;
    } else {
      # We hit another node with possible children.
      my $obj = $self->{capabilityGroupCache}{$level};
      $groups->{$level} = 1;
      my %toBeExpanded; @toBeExpanded{$cce->scalar_to_array($obj->{capabilities})} = ();
      my $expanded = $self->expandCaps(\%toBeExpanded,$groups);
      @caps{(keys %caps, keys %$expanded)} = ();
    }
  }
  return \%caps;
}


# Description:  given a cce-level capability name, returns a yay or nay if
# the current user has it or not
sub getCapable {
  my $self = shift;
  my $capName = shift;

  my $cce = $self->{cce};

  my ($okwhoami, $oid) = $cce->whoami();
  my ($ok, $capabilities) = $cce->get($oid);


  my %mycaps = map {$_ => 1} $cce->scalar_to_array($capabilities->{"capabilities"});
  if ($capabilities->{systemAdministrator} || defined($mycaps{$capName})) {
    return 1;
  }
  return 0;
}


sub listMyCaps {
  my $self = shift;
  
  if ($self->{_myCaps}) {
    return $self->{_myCaps};
  }
  
  my $cce = $self->{cce};

  my $oid = $cce->whoami();
  if ($oid == -1) {
    $cce->fail("[[sauce-basic.notAuthenticated]]");
    return {};
  }
  my ($ok, $obj) = $cce->get($oid);
  
  my %capLevels; @capLevels{($cce->scalar_to_array($obj->{capLevels}),
		  $cce->scalar_to_array($obj->{uiRights}))} = ();

  my %ret;
  my $expanded = $self->expandCaps(\%capLevels);
  @ret{keys %$expanded} = ();

  $self->{_myCaps} = \%ret;
  return \%ret;
}

# Gien a hash-ref, returns a boolean whether this user is allowed to assign 
# these capabilities.  (1 = everything a okay)
sub allowedCaps {
  my $self = shift;
  my $caps = shift;

  for my $cap (keys %$caps) {
	if (!$self->getCapable($cap)) {
		return 0;
	}
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
