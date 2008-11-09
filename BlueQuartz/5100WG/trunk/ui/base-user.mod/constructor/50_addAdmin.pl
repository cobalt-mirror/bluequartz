#!/usr/bin/perl -w -I/usr/sausalito/perl -I.

# Author: Kevin K.M. Chiu
# Copyright 2000, Cobalt Networks.  All rights reserved.
# $Id: 50_addAdmin.pl 3 2003-07-17 15:19:15Z will $

use strict;
use CCE;
use I18n;

my $cce = new CCE;
$cce->connectuds();

my $i18n = new I18n;

my $uiRight = "systemAdministrator";

# check if admin exists already
my (@oids) = $cce->find("User", {name => "admin"});
if($#oids >= 0) {
  # make sure admin has "systemAdministrator" in uiRights
  # this is to provide backward compatibility to older systems that depends on
  # systemAdministrator flag instead of uiRights to determine UI access rights
  my ($ok, $obj) = $cce->get($oids[0]);
  my @uiRights = $cce->scalar_to_array($obj->{uiRights});
  # add right if it did not exist
  if(! grep /^$uiRight$/, @uiRights) {
    push @uiRights, $uiRight;
  }
  my $uiRightsString = $cce->array_to_scalar(@uiRights);
  $cce->set($oids[0], "", {uiRights => $uiRightsString});

  $cce->bye();
  exit 1;
}

$i18n->setLocale(I18n::i18n_getSystemLocale($cce));

# add
my ($success) = $cce->create("User", {
    fullName => $i18n->get("administrator",{},"base-user"),
    localePreference => "browser",
    name => "admin",
    password => "admin",
    stylePreference => "trueBlue",
    systemAdministrator => 1,
    uiRights => "&$uiRight&"
});

# reserve the admin email alias
#if ($success) {
#	$success = $cce->create("EmailAlias", { 'name' => 'admin', 
#						action => '*RESERVED*' });
#}

$cce->bye();

# failed?
if(!$success) {
  exit 1;
}

# make sure the shell is /bin/bash
`/usr/sbin/usermod -s /bin/bash admin`;
exit 0;
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
