#!/usr/bin/perl -I /usr/sausalito/perl
# Copyright 2001 Sun Microsystems Inc.
# File: updatesecurityLevels.pl
# Author: Michael Waychison <michael.waychison@sun.com>
# $Id: SecurityLevels.pm 3 2003-07-17 15:19:15Z will $
#
# Script the simplify the constructor creation of CapabilityGroup objects used 
# be the arbitrary Security Level framework.

package SecurityLevels;
my $DEBUG=0;

use strict;
use CCE;
use Data::Dumper;

sub new {
  my $proto = shift;
  my $cce = shift;
  my $self = {};
  if (!$cce) {
    $cce = new CCE();
    $cce->connectuds();
  }  
  $self->{cce} = $cce;
  bless($self, $proto);
  return $self;
}


sub updateSecurityLevels {
  my ($self, $securityLevelName, $nameTag, $nameTagHelp, @capabilities) = @_;
    
  my $cce = $self->{cce};

  my @finds = $cce->find("CapabilityGroup", {name=>$securityLevelName});
  
  # This routine checks to see if the contents of two lists are the same.  
  # The two lists can be in any arbitrary order.
  # Takes two array references and returns a bool.
  my $matchCaps = sub {
    my ($l1,$l2) = @_; # the two lists given
    my $count = 0;  # the count of matched elements
    $DEBUG && print STDERR Dumper ($l1) . "\n"  . Dumper( $l2);
    for my $e (@$l1) {
      my $flag = 0;
      $DEBUG && print STDERR "\$e: $e\n";
      for (my $i; $i < scalar (@$l2); $i++) {
        $DEBUG && print STDERR "\t\$l2->[\$i]: " . $l2->[$i] . "\n";
        if ($e eq $l2->[$i]) {
          # We found a non-matching element,  stop here.
          undef ($l2->[$i]);
          $count++;
          $flag=1;
          last;
        }
      }
      $flag || return 0;
    }
    # Check to see if all the elements matched, if so, return true.
    ($count == scalar @$l2) && return 1;
  };
  
  
  
  if (0 ==  (scalar (@finds))) {
    # create new object...
    print STDERR "Creating CapabilityGroup '$securityLevelName'... ";
    my ($ok) = $cce->create("CapabilityGroup", {
      name=> $securityLevelName, 
      nameTag =>$nameTag,
      nameTagHelp =>$nameTagHelp,
      capabilities => ($cce->array_to_scalar(@capabilities)) } );
    ($ok &&
      print STDERR "done\n")
      || print STDERR "failed\n";
    $self->{oid} = $cce->oid();
      
  
  } else {
    # check if we need to update the object..
    my ($ok, $obj, @info) = $cce->get($finds[0]);
    $self->{oid} = $finds[0];
    my %changes; # a list of the changes that need to be made..
  
    # check for changes in each individual element.
    if ($obj->{name} ne $securityLevelName) {
      $changes{name} = $securityLevelName;
    }
    if ($obj->{nameTag} ne $nameTag) {
      $changes{nameTag} = $nameTag;
    }
    if ($obj->{nameTagHelp} ne $nameTagHelp) {
      $changes{nameTagHelp} = $nameTagHelp;
    }
    if (!&$matchCaps(\@capabilities, [($cce->scalar_to_array($obj->{capabilities}))])) {
      $changes{capabilities} = $cce->array_to_scalar(@capabilities);
    }
    
    if (scalar keys %changes) {
      # we need to update,  something has changed..
      print STDERR "Updating CapabilityGroup '$securityLevelName'... ";
      my ($ok) = $cce->set($finds[0], "", \%changes);
      ($ok &&
        print STDERR "done\n")
        || print STDERR "failed\n";
    }
  }
}

sub setSortOrder {
  my $self = shift;
  my $order = shift;
  my $cce = $self->{cce};
  $cce->set($self->{oid}, "", {sort => $order});
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
