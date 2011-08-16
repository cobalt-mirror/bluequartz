#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
# $Id: update_site_admins.pl,v 1.6 2001/12/07 00:27:13 pbaltz Exp $
#
# keep all site admins capabilities in sync as admin users give and
# take away features from a virtual site

use CCE;
use Base::User::Capabilities;

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();
my $vsite_old = {};

my @site_admins = ();

# handle site admin creation and modifying the siteAdminCaps property
if ($vsite->{CLASS} eq 'Vsite')
{
	$vsite_old = $cce->event_old();
	@site_admins = $cce->findx('User', 
					{ 'site' => $vsite->{name}, 'capLevels' => 'siteAdmin' },
					{}, '', '');
}
else
{
	# must be a user
	if ($vsite->{capLevels} !~ /siteAdmin/)
	{
		$cce->bye('SUCCESS');
		exit(0);
	}

	push @site_admins, $cce->event_oid();
	
	# need to get the vsite info if this is a user
	my ($oid) = $cce->findx('Vsite', { 'name' => $vsite->{site} });
	(my $ok, $vsite, $vsite_old) = $cce->get($oid);

	if (!$ok)
	{
		$cce->bye('FAIL', '[[base-vsite.cantReadVsite]]');
		exit(1);
	}
}

# setup lists of caps for user updates
my @old_caps = $cce->scalar_to_array($vsite_old->{siteAdminCaps});
my @new_caps = $cce->scalar_to_array($vsite->{siteAdminCaps});

# get the caps class
my $capabilities = new Base::User::Capabilities($cce);

# go through and reset all site admins' capLevels
for my $admin (@site_admins)
{
	if (!$capabilities->modUserCaps($admin, \@old_caps, \@new_caps))
	{
		$cce->bye('FAIL', '[[base-vsite.cantUpdateSiteAdminCaps]]');
		exit(0);
	}
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
