#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: updateAllCapabilities.pl,v 1.2 2001/06/29 04:56:37 pbaltz Exp $

use CCE;
use Base::User::Capabilities;

my $cce = new CCE();

$cce->connectfd();

# create the Capability object that takes care of expansions.
my $Capability = new Base::User::Capabilities($cce);

# This may be the unwanted behaviour, but for now, if one user fails, then 
# they all fail..    oh well.. (notice that this will need to be changed for
# products that use vsites where one user may be able to admin some user's caps,
# but not all of them..
my $ok = 1;

# loop and mod re-expand each user..
my @Useroids = $cce->find("User");
for my $useroid (@Useroids) {
  my $obj;
  ($ok, $obj) = $cce->get($useroid); 
  last if (!$ok);

  # get an array of capabilityGroups that are being set..
  my %capLevels; @capLevels{$cce->scalar_to_array($obj->{capLevels}),$cce->scalar_to_array($obj->{uiRights})} = ();

  # expand the capabilityGroups into cce-level capabilities 
  my $caps = $Capability->expandCaps(\%capLevels); 
# THIS ISN'T NEEDED
# only a .systemAdminstrator can create capabilityGroups, and that is the
# only thing that will trigger this script.   As such, we don't need this check
# (which broke a little while back anyhow...)
#  # make sure that this user is god to set these capabilities
#  if (!(( defined(($Capability->listMyCaps())->{systemAdministrator})) #god
#    || (!$Capability->checkCaps($caps)))) {
#    
#    # not allowed!!!    somebody is trying to give perms they don't even have!
#    $cce->warn("[[sauce-basic.permsNotAvailable]]");
#    $ok = 0;
#    last;
#  } 
       
  my $capsScalar = $cce->array_to_scalar(keys %$caps);
  ($ok) = $cce->set($useroid, "", {
        capabilities=>$capsScalar
        });
  last if (!$ok);
}

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
