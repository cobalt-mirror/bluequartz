#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: updateCapabilities.pl,v 1.3 2001/06/30 02:18:34 pbaltz Exp $
#

use CCE;
use Base::User::Capabilities;

my $cce = new CCE();

$cce->connectfd();

# get event data
my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my $Capability = new Base::User::Capabilities($cce);

my %capLevels = map {$_ => 1} $cce->scalar_to_array($obj->{capLevels});

my $caps = $Capability->expandCaps(\%capLevels); 

# make a list of all the caps that we are taking away from the user,
#  the current user MUST be able to 
my $old_obj = $cce->event_old();
my %old_capLevels = map {$_=>1} $cce->scalar_to_array($old_obj->{capLevels});
my $old_caps = $Capability->expandCaps(\%old_capLevels);

my %caps_being_removed;
my %caps_being_added;

foreach my $old_cap (keys %$old_caps) {
  # make a list of caps we want to remove
  if (! exists $caps->{$old_cap}) {
    $caps_being_removed{$old_cap} = 1; 
  } 
}

foreach my $newcap (keys %$caps) {
  # make a list of caps we are adding
  if (!exists $old_caps->{$newcap}) {
    $caps_being_added{$newcap} = 1;
  }
}

# check if we can infact do the caps we want to remove.
if (!$Capability->allowedCaps(\%caps_being_removed)) {
  $cce->warn("[[sauce-basic.needPermToRemovePerm]]");
  $cce->bye("FAIL");
  exit(0);
}

# check that this user has the privilege to do this..
# get info to see if i'm god.
#
# (Commented out for now as it periodically fails user creation on cmuImport)
#if (!$Capability->allowedCaps(\%caps_being_added)) {
#  $cce->warn("[[sauce-basic.permsNotAvailable]]"); 
#  $cce->bye("FAIL");
#  exit(0);
#}

my $capsScalar = $cce->array_to_scalar(keys %$caps);
my ($ok) = $cce->set($oid, "", 
            {
                capabilities => $capsScalar
            });

if (!$ok) {
  $cce->bye("FAIL");
} else {
  $cce->bye("SUCCESS");
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
