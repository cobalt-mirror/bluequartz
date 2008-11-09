#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: maxusers.pl,v 1.8.2.1 2002/03/05 01:19:07 pbaltz Exp $
# Copyright 2000-2002 Sun Microsystems, Inc., All rights reserved.
#
# enforce the maxusers requirement for vsites

use CCE;

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my $user_new = $cce->event_new();

# special case, if the site property of the user is not set assume it's a global user
if ($user_new->{site} eq '')
{
    $cce->bye('SUCCESS');
    exit(0);
}

# get the vsite info
my ($ok, $vsite) = $cce->get(($cce->find("Vsite", { 'name' => $user_new->{site} }))[0]);
if (not $ok)
{
    $cce->bye('FAIL', '[[base-vsite.cantReadVsite]]');
    exit(1);
}

# get current number of users and check to see if this user
# will put the site over the limit
# note: the user being created will be found in this search 
#		so it's actually current users + 1
my $num_users = scalar($cce->find('User', { 'site' => $user_new->{site} }));

if ($num_users > $vsite->{maxusers})
{
	$cce->bye('FAIL', 'overUserLimit', { 'fqdn' => $vsite->{fqdn} });
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

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
