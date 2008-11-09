#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: log_account.pl,v 1.3.2.1 2002/06/18 21:50:53 naroori Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# manages the per-site log user account and ServiceQuota object
# for site-level split logs and usage stats

use CCE;
use Sauce::Config;
use Sauce::Util;
use Base::User qw(useradd userdel);

my $DEBUG = 0;
$DEBUG && warn `date`."$0\n";

# make sure umask is sane, we create and chmod dirs here
umask(002);

my $cce = new CCE;
$cce->connectfd();

my $err; # Global error message/return state

# We're triggered on Vsite create/mod/edit
my $oid = $cce->event_oid(); 
my $obj = $cce->event_object(); # Vsite
my $obj_new = $cce->event_new();
my $obj_old = $cce->event_old();

# Find matching ServiceQuota objects
my $sitegroup = $obj_old->{name};
$sitegroup ||= $obj->{name};
my @oids = $cce->find('ServiceQuota', {
	'site' => $sitegroup,
	'label' => '[[base-sitestats.statsQuota]]',
	}); 

if($cce->event_is_destroy())
{
	# destroy the associated ServiceQuota object
	$DEBUG && warn "Deleting ServiceQuota objects:\n";

	foreach my $i (@oids)
	{
		my ($ret, @info) = $cce->destroy($i);
		$err .= '[[base-sitestats.couldNotClearStatsQuotaMon]]' 
			unless ($ret);
		$DEBUG && warn "destroy $i $ret\n";
	}
	
	# Delete the site logs user
	my $user = &group_to_user($sitegroup);
	
	if(getpwnam($user))
	{
		# delete user, no need to tell userdel to remove dir
		# vsite destroy will take care of that
		userdel(0, $user);
	}
} 
elsif ($cce->event_is_create())
{
	# make sure vsite_create.pl has created the system group already
	if (!$obj->{name})
	{
		$cce->bye('DEFER');
		exit(0);
	}

	# create a ServiceQuota object
	$DEBUG && warn "Creating ServiceQuota object\n";

	$owner = &group_to_user($sitegroup);

	my($ret) = $cce->create('ServiceQuota', { 
		'label' => '[[base-sitestats.statsQuota]]',
		'site' => $obj->{name},
		'account' => $owner,
		'isgroup' => 0,
		'quota' => 20,
		'used' => 0,
		});
		
	$err .= '[[base-sitestats.couldNotSetStatsQuotaMon]]' 
		unless ($ret);
	
	($ret) = $cce->set($oid, 'SiteStats', { 
		'owner' => $owner,
		});
		
	$err .= '[[base-sitestats.couldNotSetStatsQuotaMon]]' 
		unless ($ret);

	# Create the site logs user
    my $user = {
                    'comment' => $obj->{fqdn},
                    'homedir' => $obj->{basedir}.'/logs',
                    'group' => $obj->{name},
                    'shell' => Sauce::Config::bad_shell(),
                    'name' => $owner,
                    };

   	# this also creates the logs directory
	if (!(useradd($user))[0])
	{
		$err .= '[[base-sitestats.couldNotCreateStatsUser]]';
	}
	else
	{
		# make sure the dir permissions are correct
		Sauce::Util::chmodfile(02751, $user->{homedir});
	}
}

if($err)
{
	$cce->bye('FAIL', $err);
	exit 1;
}
else
{
	$cce->bye('SUCCESS');
	exit 0;
}


sub group_to_user
{
	my $x = $_[0];
	$x =~ tr/[a-z]/[A-Z]/;
	return $x.'-logs'; 
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
